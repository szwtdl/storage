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

class Qiniu implements IService
{
    protected Auth $auth;

    protected Config $config;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
        try {
            $this->auth = new Auth($this->config->getAccessKey(), $this->config->getSecretKey());
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function buckets(): array
    {
        $bucketMgr = new BucketManager($this->auth);
        $bucketList = $bucketMgr->listbuckets()[0];
        $items = [];
        foreach ($bucketList as $item) {
            $items[] = [
                'name' => $item['id'],
                'region' => $item['region'],
                'created_at' => date('Y-m-d H:i:s', $item['ctime']),
            ];
        }
        return $items;
    }

    public function listObj(array $options = []): array
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
        } catch (\Exception $exception) {
            return [];
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
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function delete(string $object)
    {
        try {
            $bucketManager = new BucketManager($this->auth);
            return $bucketManager->delete($this->config->getBucket(), $object);
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function download(string $object, string $filePath, array $options = []): bool
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
                throw new \Exception('Curl error: ' . curl_error($ch));
            }
            curl_close($ch);
            $result = file_put_contents($filePath, $fileContent);
            if ($result === false) {
                throw new \Exception("Failed to save file to {$filePath}");
            }
            return true;
        } catch (\Exception $exception) {
            print_r($exception->getMessage());
            return false;
        }
    }
}
