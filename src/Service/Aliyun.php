<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Storage\Service;

use OSS\Core\OssException;
use OSS\Credentials\StaticCredentialsProvider;
use OSS\OssClient;

class Aliyun implements IService
{
    protected OssClient $ossClient;

    protected Config $config;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
        try {
            $clientConfig = [
                'provider' => new StaticCredentialsProvider($this->config->getAccessKey(), $this->config->getSecretKey()),
                'endpoint' => $this->config->getEndpoint(),
                'region' => $this->config->getOption('region'),
            ];
            $this->ossClient = new OssClient($clientConfig);
        } catch (OssException $e) {
            return $e->getMessage();
        }
    }

    public function buckets(): array
    {
        try {
            $bucketListInfo = $this->ossClient->listBuckets();
            $bucketList = $bucketListInfo->getBucketList();
            $items = [];
            foreach ($bucketList as $bucket) {
                $items[] = [
                    'name' => $bucket->getName(),
                    'region' => $bucket->getLocation(),
                    'class' => $bucket->getStorageClass(),
                    'domain' => $bucket->getExtranetEndpoint(),
                    'created_at' => date('Y-m-d H:i:s', strtotime($bucket->getCreateDate())),
                ];
            }
            return $items;
        } catch (OssException $e) {
            return [];
        }
    }

    public function listObj(array $options = []): array
    {
        try {
            $option = [
                'max-keys' => $options['max'] ?? 200,
                'prefix' => $options['prefix'] ?? '/',
            ];
            $result = $this->ossClient->listObjects($this->config->getBucket(), $option);
            $objectList = $result->getObjectList();
            print_r($result->getPrefixList());
            print_r($result);
        } catch (OssException $e) {
            print_r($e->getMessage());
        }
        return [];
    }

    public function upload(string $filePath, string $object)
    {
        try {
            return $this->ossClient->uploadFile($this->config->getBucket(), $object, $filePath);
        } catch (OssException $e) {
            return $e->getMessage();
        }
    }

    public function delete(string $object)
    {
        try {
            return $this->ossClient->deleteObject($this->config->getBucket(), $object);
        } catch (OssException $e) {
            return $e->getMessage();
        }
    }

    public function download(string $object, string $filePath, array $options = []): bool
    {
        try {
            $options = array_merge($options, [
                OssClient::OSS_FILE_DOWNLOAD => $filePath,
            ]);
            $this->ossClient->getObject($this->config->getBucket(), $object, $options);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
