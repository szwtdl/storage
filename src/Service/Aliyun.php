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
use OSS\Http\RequestCore_Exception;
use OSS\OssClient;
use Szwtdl\Storage\Exception\Exception;
use Szwtdl\Storage\Exception\InvalidArgumentException;

class Aliyun implements IService
{
    protected OssClient $ossClient;

    protected Config $config;

    public function __construct(array $config)
    {
        try {
            $this->config = new Config($config);
            $clientConfig = [
                'provider' => new StaticCredentialsProvider($this->config->getAccessKey(), $this->config->getSecretKey()),
                'endpoint' => $this->config->getEndpoint(),
                'region' => $this->config->getOption('region'),
            ];
            $this->ossClient = new OssClient($clientConfig);
        } catch (OssException|InvalidArgumentException $e) {
            echo "错误信息:".$e->getMessage();
            throw new Exception("初始化失败:" . $e->getMessage());
        }
    }

    public function listBuckets(): array
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
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("获取列表失败:" . $e->getMessage());
        }
    }

    public function createBucket(string $name, array $options = array())
    {
        try {
            $options = array_merge($options, [
                OssClient::OSS_STORAGE => OssClient::OSS_STORAGE_IA
            ]);
            return $this->ossClient->createBucket($name, OssClient::OSS_ACL_TYPE_PUBLIC_READ, $options);
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("创建失败:" . $e->getMessage());
        }
    }

    public function deleteBucket(string $name)
    {
        try {
            return $this->ossClient->deleteBucket($name);
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("删除失败:" . $e->getMessage());
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
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("获取列表失败:" . $e->getMessage());
        }
        return [];
    }

    public function upload(string $filePath, string $object)
    {
        try {
            return $this->ossClient->uploadFile($this->config->getBucket(), $object, $filePath);
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("上传失败:" . $e->getMessage());
        }
    }

    public function delete(string $object)
    {
        try {
            return $this->ossClient->deleteObject($this->config->getBucket(), $object);
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("删除失败:" . $e->getMessage());
        }
    }

    public function download(string $object, string $filePath, array $options = [])
    {
        try {
            $options = array_merge($options, [
                OssClient::OSS_FILE_DOWNLOAD => $filePath,
            ]);
            return $this->ossClient->getObject($this->config->getBucket(), $object, $options);
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("下载失败:" . $e->getMessage());
        }
    }
}
