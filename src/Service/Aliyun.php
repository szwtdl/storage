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

    public function createBucket(string $name, array $options = array()): array
    {
        try {
            $options = array_merge($options, [
                OssClient::OSS_STORAGE => OssClient::OSS_STORAGE_IA
            ]);
            $result = $this->ossClient->createBucket($name, OssClient::OSS_ACL_TYPE_PUBLIC_READ, $options);
            return [
                'name' => $name,
                'status' => 'created',
                'success' => true,
                'raw' => $result,
            ];
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("创建失败:" . $e->getMessage());
        }
    }

    public function deleteBucket(string $name): array
    {
        try {
            $result = $this->ossClient->deleteBucket($name);
            return [
                'name' => $name,
                'status' => 'deleted',
                'success' => true,
                'raw' => $result,
            ];
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("删除失败:" . $e->getMessage());
        }
    }

    public function listObj(array $options = []): array
    {
        try {
            $option = [
                'max-keys' => $options['max'] ?? 200,
                'prefix' => $options['prefix'] ?? '',
                'delimiter' => $options['delimiter'] ?? '',
                'marker' => $options['marker'] ?? '',
            ];
            $result = $this->ossClient->listObjects($this->config->getBucket(), $option);
            $objectList = $result->getObjectList();
            $items = [];
            if (!empty($objectList)) {
                foreach ($objectList as $object) {
                    $key = $object->getKey();
                    $items[] = [
                        'path' => $key,
                        'name' => basename($key),
                        'size' => (int) $object->getSize(),
                        'type' => $this->isDirectoryPath($key) ? 'dir' : 'file',
                        'etag' => $object->getEtag(),
                        'url' => $this->buildObjectUrl($key),
                        'last_modified' => date('Y-m-d H:i:s', strtotime($object->getLastModified())),
                    ];
                }
            }

            foreach ($result->getPrefixList() ?: [] as $prefix) {
                $items[] = [
                    'path' => $prefix->getPrefix(),
                    'name' => basename(rtrim($prefix->getPrefix(), '/')),
                    'size' => 0,
                    'type' => 'dir',
                    'etag' => null,
                    'url' => $this->buildObjectUrl($prefix->getPrefix()),
                    'last_modified' => null,
                ];
            }

            return $items;
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("获取列表失败:" . $e->getMessage());
        }
    }

    public function upload(string $filePath, string $object): array
    {
        try {
            $this->assertLocalFileExists($filePath);
            $result = $this->ossClient->uploadFile($this->config->getBucket(), $object, $filePath);
            return [
                'path' => $object,
                'name' => basename($object),
                'size' => (int) filesize($filePath),
                'etag' => $result['etag'] ?? null,
                'url' => $this->buildObjectUrl($object),
                'success' => true,
                'raw' => $result,
            ];
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("上传失败:" . $e->getMessage());
        }
    }

    public function delete(string $object): array
    {
        try {
            $result = $this->ossClient->deleteObject($this->config->getBucket(), $object);
            return [
                'path' => $object,
                'success' => true,
                'raw' => $result,
            ];
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("删除失败:" . $e->getMessage());
        }
    }

    public function download(string $object, string $filePath, array $options = []): array
    {
        try {
            $options = array_merge($options, [
                OssClient::OSS_FILE_DOWNLOAD => $filePath,
            ]);
            $result = $this->ossClient->getObject($this->config->getBucket(), $object, $options);
            return [
                'path' => $object,
                'save_as' => $filePath,
                'size' => file_exists($filePath) ? (int) filesize($filePath) : 0,
                'url' => $this->buildObjectUrl($object),
                'success' => true,
                'raw' => $result,
            ];
        } catch (OssException|RequestCore_Exception $e) {
            throw new Exception("下载失败:" . $e->getMessage());
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
