<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Storage\Service;

use Qcloud\Cos\Client;
use Szwtdl\Storage\Exception\Exception;
use Throwable;

class Tencent implements IService
{
    protected Client $client;

    protected Config $config;

    public function __construct(array $config)
    {
        try {
            $this->config = new Config($config);
            $cosConfig = [
                'region' => $this->config->getOption('region'),
                'scheme' => 'https',
                'credentials' => [
                    'secretId' => $this->config->getAccessKey(),
                    'secretKey' => $this->config->getSecretKey(),
                ],
            ];
            $this->client = new Client($cosConfig);
        } catch (Throwable $e) {
            throw new Exception("初始化错误:".$e->getMessage());
        }
    }

    public function getTemporaryCredentials(array $options = array()): array
    {
        $stsOptions = $this->resolveStsOptions($options);

        if (!empty($stsOptions['credentials']) && is_array($stsOptions['credentials'])) {
            return $this->normalizeTemporaryCredentials($stsOptions['credentials'], $stsOptions);
        }

        if (class_exists('\\QCloud\\COSSTS\\Sts')) {
            $sts = new \QCloud\COSSTS\Sts();
            $result = $sts->getTempKeys([
                'url' => $stsOptions['url'] ?? 'https://sts.tencentcloudapi.com/',
                'domain' => $stsOptions['domain'] ?? 'sts.tencentcloudapi.com',
                'proxy' => $stsOptions['proxy'] ?? '',
                'secretId' => $this->config->getAccessKey(),
                'secretKey' => $this->config->getSecretKey(),
                'bucket' => $this->getBucket(),
                'region' => $this->config->getOption('region'),
                'durationSeconds' => $stsOptions['duration_seconds'] ?? 3600,
                'allowPrefix' => $stsOptions['allow_prefix'] ?? ['/*'],
                'allowActions' => $stsOptions['allow_actions'] ?? ['name/cos:*'],
            ]);

            if (!empty($stsOptions['condition'])) {
                $result['condition'] = $stsOptions['condition'];
            }

            return $this->normalizeTemporaryCredentials($result['credentials'] ?? [], $result);
        }

        throw new Exception('获取临时密钥失败:未安装腾讯云 STS SDK，或缺少 credentials 配置');
    }

    public function listBuckets(): array
    {
        try {
            $result = $this->client->listBuckets();
            $bucketList = $result['Buckets'][0]['Bucket'] ?? [];
            $items = [];
            if (!empty($bucketList) && is_array($bucketList)) {
                foreach ($bucketList as $item) {
                    $items[] = [
                        'name' => $item['Name'],
                        'region' => $item['Location'],
                        'class' => $item['BucketType'],
                        'created_at' => date('Y-m-d H:i:s', strtotime($item['CreationDate'])),
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
            $result = $this->client->createBucket(array('Bucket' => $name));
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
            $result = $this->client->deleteBucket(array('Bucket' => $name));
            return [
                'name' => $name,
                'status' => 'deleted',
                'success' => true,
                'raw' => $result,
            ];
        } catch (Throwable $e) {
            throw new Exception("删除失败".$e->getMessage());
        }
    }

    public function listObj(array $options = []): array
    {
        try {
            $delimiter = empty($options['delimiter']) ? '' : $options['delimiter'];
            $marker = empty($options['marker']) ? '' : $options['marker'];
            $prefix = empty($options['prefix']) ? '' : $options['prefix'];
            $MaxKeys = empty($options['max_keys']) ? 100 : $options['max_keys'];
            $result = $this->client->listObjects([
                'Bucket' => $this->getBucket(),
                'Delimiter' => $delimiter,
                'EncodingType' => 'url',
                'Marker' => $marker,
                'Prefix' => $prefix,
                'MaxKeys' => $MaxKeys,
            ]);
            $items = [];

            foreach ($result['Contents'] ?? [] as $object) {
                $key = urldecode($object['Key']);
                $items[] = [
                    'path' => $key,
                    'name' => basename($key),
                    'size' => (int) ($object['Size'] ?? 0),
                    'type' => $this->isDirectoryPath($key) ? 'dir' : 'file',
                    'etag' => isset($object['ETag']) ? trim((string) $object['ETag'], '"') : null,
                    'url' => $this->buildObjectUrl($key),
                    'last_modified' => isset($object['LastModified']) ? date('Y-m-d H:i:s', strtotime($object['LastModified'])) : null,
                ];
            }

            foreach ($result['CommonPrefixes'] ?? [] as $prefixItem) {
                $prefixPath = urldecode($prefixItem['Prefix']);
                $items[] = [
                    'path' => $prefixPath,
                    'name' => basename(rtrim($prefixPath, '/')),
                    'size' => 0,
                    'type' => 'dir',
                    'etag' => null,
                    'url' => $this->buildObjectUrl($prefixPath),
                    'last_modified' => null,
                ];
            }

            return $items;
        } catch (Throwable $e) {
            throw new Exception("获取失败".$e->getMessage());
        }
    }

    public function upload(string $filePath, string $object): array
    {
        try {
            $this->assertLocalFileExists($filePath);
            $result = $this->client->upload(
                $this->getBucket(),
                $object,
                fopen($filePath, 'rb')
            );
            return [
                'path' => $object,
                'name' => basename($object),
                'size' => (int) filesize($filePath),
                'etag' => isset($result['ETag']) ? trim((string) $result['ETag'], '"') : null,
                'url' => $this->buildObjectUrl($object),
                'success' => true,
                'raw' => $result,
            ];
        } catch (Throwable $e) {
            throw new Exception("上传失败:".$e->getMessage());
        }
    }

    public function delete(string $object): array
    {
        try {
            $result = $this->client->deleteObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object,
            ]);
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
            $result = $this->client->getObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object,
                'SaveAs' => $filePath,
            ]);
            return [
                'path' => $object,
                'save_as' => $filePath,
                'size' => file_exists($filePath) ? (int) filesize($filePath) : 0,
                'url' => $this->buildObjectUrl($object),
                'success' => true,
                'raw' => $result,
            ];
        } catch (Throwable $e) {
            throw new Exception("下载失败:".$e->getMessage());
        }
    }

    private function getBucket(): string
    {
        return $this->config->getBucket() . '-' . $this->config->getEndpoint();
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

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function resolveStsOptions(array $options): array
    {
        $configOptions = $this->config->getOption('sts', []);
        if (!is_array($configOptions)) {
            $configOptions = [];
        }

        return array_merge($configOptions, $options);
    }

    /**
     * @param array<string, mixed> $credentials
     * @param array<string, mixed> $raw
     */
    private function normalizeTemporaryCredentials(array $credentials, array $raw): array
    {
        return [
            'type' => 'sts',
            'credentials' => [
                'access_key_id' => $credentials['tmpSecretId'] ?? $credentials['access_key_id'] ?? null,
                'access_key_secret' => $credentials['tmpSecretKey'] ?? $credentials['access_key_secret'] ?? null,
                'session_token' => $credentials['sessionToken'] ?? $credentials['token'] ?? $credentials['session_token'] ?? null,
            ],
            'expiration' => $raw['expiration'] ?? null,
            'expired_at' => isset($raw['expiredTime']) ? (int) $raw['expiredTime'] : (isset($raw['expired_at']) ? (int) $raw['expired_at'] : null),
            'bucket' => $this->getBucket(),
            'region' => $this->config->getOption('region'),
            'domain' => $this->config->getOption('domain'),
            'success' => true,
            'raw' => $raw,
        ];
    }
}
