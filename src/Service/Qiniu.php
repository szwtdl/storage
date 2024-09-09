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
use Szwtdl\Storage\Exception\InvalidArgumentException;

class Qiniu implements IService
{
    protected Auth $auth;

    protected Config $config;

    public function __construct(array $config)
    {
        try {
            $this->config = new Config($config);
            $this->auth = new Auth($this->config->getAccessKey(), $this->config->getSecretKey());
        } catch (Exception|InvalidArgumentException $e) {
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
        }catch (Exception $e){
            throw new Exception("获取失败:".$e->getMessage());
        }
    }

    public function createBucket(string $name, array $options = array()): array
    {
        try {
            $bucketMgr = new BucketManager($this->auth);
            $region = empty($options['region']) ? 'z0': $options['region'];
            return $bucketMgr->createBucket($name,$region);
        }catch (Exception $e){
            throw new Exception("创建失败:".$e->getMessage());
        }
    }

    public function deleteBucket(string $name): array
    {
        try {
            $bucketMgr = new BucketManager($this->auth);
            return $bucketMgr->deleteBucket($name);
        }catch (Exception $e){
            throw new Exception("删除失败:".$e->getMessage());
        }
    }

    public function listObj(array $options = [])
    {
        $prefix = $options['prefix'] ?? '';
        $marker = $options['marker'] ?? '';
        $limit = $options['limit'] ?? 100;
        $delimiter = $options['delimiter'] ?? '/';
        try {
            $bucketMgr = new BucketManager($this->auth);
            [$ret, $err] = $bucketMgr->listFiles($this->config->getBucket(), $prefix, $marker, $limit, $delimiter);
            if ($err !== null) {
                return $err;
            }
            $items = [];
            foreach ($ret['items'] as $item) {
                $items[] = [
                    'path' => $item['key'],
                    'hash' => $item['hash'],
                    'size' => $item['fsize'],
                    'type' => $item['type'],
                ];
            }
            return $items;
        } catch (Exception $e) {
            throw new Exception("获取失败:".$e->getMessage());
        }
    }

    public function upload(string $filePath, string $object)
    {
        try {
            $uploadMgr = new UploadManager();
            $token = $this->auth->uploadToken($this->config->getBucket());
            [$ret, $err] = $uploadMgr->putFile($token, $object, $filePath, null, 'application/octet-stream', true, null, 'v2');
            if ($err != null) {
                return $err;
            }
            return $ret;
        } catch (Exception $e) {
            throw new Exception("上传失败:".$e->getMessage());
        }
    }

    public function delete(string $object): array
    {
        try {
            $bucketManager = new BucketManager($this->auth);
            return $bucketManager->delete($this->config->getBucket(), $object);
        } catch (Exception $e) {
            throw new Exception("删除失败:".$e->getMessage());
        }
    }

    public function download(string $object, string $filePath, array $options = []): int
    {
        try {
            $baseUrl = "{$this->config->getOption('domain')}/{$object}";
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
            return $result;
        } catch (Exception $e) {
            throw new Exception("下载失败:".$e->getMessage());
        }
    }
}
