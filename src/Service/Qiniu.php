<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Storage\Service;

use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Szwtdl\Storage\Exception\Exception;
use Throwable;

class Qiniu implements IService
{
    protected Auth $auth;

    protected Config $config;

    public function __construct(array $config)
    {
        try {
            $this->config = new Config($config);
            $this->auth = new Auth($this->config->getAccessKey(), $this->config->getSecretKey());
        } catch (Throwable $e) {
            throw new Exception("初始化失败:".$e->getMessage());
        }
    }

    public function listBuckets(): array
    {
        try {
            $bucketMgr = new BucketManager($this->auth);
            $bucketList = $bucketMgr->listbuckets()[0];
            $items = [];
            if (!empty($bucketList)) {
                foreach ($bucketList as $item) {
                    $items[] = [
                        'name' => $item['id'],
                        'region' => $item['region'],
                        'created_at' => date('Y-m-d H:i:s', $item['ctime']),
                    ];
                }
            }
            return $items;
        } catch (Throwable $e) {
            throw new Exception("获取失败:".$e->getMessage());
        }
    }

    public function createBucket(string $name, array $options = array()): array
    {
        try {
            $bucketMgr = new BucketManager($this->auth);
            $region = empty($options['region']) ? 'z0': $options['region'];
            $result = $bucketMgr->createBucket($name,$region);
            return [
                'name' => $name,
                'status' => 'created',
                'success' => true,
                'raw' => $result,
            ];
        } catch (Throwable $e) {
            throw new Exception("创建失败:".$e->getMessage());
        }
    }

    public function deleteBucket(string $name): array
    {
        try {
            $bucketMgr = new BucketManager($this->auth);
            $result = $bucketMgr->deleteBucket($name);
            return [
                'name' => $name,
                'status' => 'deleted',
                'success' => true,
                'raw' => $result,
            ];
        } catch (Throwable $e) {
            throw new Exception("删除失败:".$e->getMessage());
        }
    }

    public function listObj(array $options = []): array
    {
        $prefix = $options['prefix'] ?? '';
        $marker = $options['marker'] ?? '';
        $limit = $options['limit'] ?? 100;
        $delimiter = $options['delimiter'] ?? '';
        try {
            $bucketMgr = new BucketManager($this->auth);
            [$ret, $err] = $bucketMgr->listFiles($this->config->getBucket(), $prefix, $marker, $limit, $delimiter);
            if ($err !== null) {
                throw new Exception($err->message());
            }
            $items = [];
            foreach ($ret['items'] ?? [] as $item) {
                $key = $item['key'];
                $items[] = [
                    'path' => $key,
                    'name' => basename($key),
                    'hash' => $item['hash'],
                    'size' => $item['fsize'],
                    'type' => $this->isDirectoryPath($key) ? 'dir' : 'file',
                    'etag' => $item['hash'],
                    'url' => $this->buildObjectUrl($key),
                    'last_modified' => isset($item['putTime']) ? date('Y-m-d H:i:s', (int) ($item['putTime'] / 10000000)) : null,
                ];
            }

            foreach ($ret['commonPrefixes'] ?? [] as $prefixPath) {
                $items[] = [
                    'path' => $prefixPath,
                    'name' => basename(rtrim($prefixPath, '/')),
                    'hash' => null,
                    'size' => 0,
                    'type' => 'dir',
                    'etag' => null,
                    'url' => $this->buildObjectUrl($prefixPath),
                    'last_modified' => null,
                ];
            }

            return $items;
        } catch (Throwable $e) {
            throw new Exception("获取失败:".$e->getMessage());
        }
    }

    public function upload(string $filePath, string $object): array
    {
        try {
            $this->assertLocalFileExists($filePath);
            $uploadMgr = new UploadManager();
            $token = $this->auth->uploadToken($this->config->getBucket());
            [$ret, $err] = $uploadMgr->putFile($token, $object, $filePath, null, 'application/octet-stream', true, null, 'v2');
            if ($err !== null) {
                throw new Exception($err->message());
            }
            return [
                'path' => $object,
                'name' => basename($object),
                'size' => (int) filesize($filePath),
                'hash' => $ret['hash'] ?? null,
                'etag' => $ret['hash'] ?? null,
                'url' => $this->buildObjectUrl($object),
                'success' => true,
                'raw' => $ret,
            ];
        } catch (Throwable $e) {
            throw new Exception("上传失败:".$e->getMessage());
        }
    }

    public function delete(string $object): array
    {
        try {
            $bucketManager = new BucketManager($this->auth);
            $result = $bucketManager->delete($this->config->getBucket(), $object);
            return [
                'path' => $object,
                'success' => true,
                'raw' => $result,
            ];
        } catch (Throwable $e) {
            throw new Exception("删除失败:".$e->getMessage());
        }
    }

    public function download(string $object, string $filePath, array $options = []): array
    {
        try {
            $domain = rtrim((string) $this->config->getOption('domain', ''), '/');
            if ($domain === '') {
                throw new Exception('缺少 domain 配置');
            }
            $baseUrl = "{$domain}/{$object}";
            $signedUrl = $this->auth->privateDownloadUrl($baseUrl);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $signedUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 禁用证书验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 禁用主机验证
            $fileContent = curl_exec($ch);
            if ($fileContent === false) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            curl_close($ch);
            $result = file_put_contents($filePath, $fileContent);
            if ($result === false) {
                throw new Exception("Failed to save file to {$filePath}");
            }
            return [
                'path' => $object,
                'save_as' => $filePath,
                'size' => (int) $result,
                'url' => $this->buildObjectUrl($object),
                'success' => true,
                'raw' => $signedUrl,
            ];
        } catch (Throwable $e) {
            throw new Exception("下载失败:".$e->getMessage());
        }
    }

    private function isDirectoryPath(string $path): bool
    {
        return substr($path, -1) === '/';
    }

    private function buildObjectUrl(string $path): ?string
    {
        $domain = rtrim((string) $this->config->getOption('domain', ''), '/');
        if ($domain === '') {
            return null;
        }

        return $domain . '/' . ltrim($path, '/');
    }

    private function assertLocalFileExists(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new Exception($filePath . ' file does not exist');
        }
    }
}
