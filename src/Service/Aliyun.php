<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Service;

use OSS\Core\OssException;
use OSS\Credentials\StaticCredentialsProvider;
use OSS\Http\RequestCore_Exception;
use OSS\OssClient;
use Szwtdl\Exception\Exception;
use Szwtdl\Exception\InvalidArgumentException;

class Aliyun implements IService
{
    private const STS_VERSION = '2015-04-01';

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

    public function getTemporaryCredentials(array $options = array()): array
    {
        $stsOptions = $this->resolveStsOptions($options);

        if (!empty($stsOptions['credentials']) && is_array($stsOptions['credentials'])) {
            return $this->normalizeTemporaryCredentials($stsOptions['credentials'], $stsOptions);
        }

        if (empty($stsOptions['role_arn'])) {
            throw new Exception('获取临时密钥失败:缺少 role_arn 或 credentials 配置');
        }

        try {
            $response = $this->requestStsCredentials($stsOptions);
            $credentials = $response['Credentials'] ?? null;
            if (!is_array($credentials)) {
                throw new Exception('获取临时密钥失败:返回结果无效');
            }

            return $this->normalizeTemporaryCredentials($credentials, $response);
        } catch (InvalidArgumentException $e) {
            throw new Exception('获取临时密钥失败:' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("获取临时密钥失败:" . $e->getMessage());
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
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     * @throws Exception
     */
    private function requestStsCredentials(array $options): array
    {
        $params = [
            'Format' => 'JSON',
            'Version' => self::STS_VERSION,
            'AccessKeyId' => $this->config->getAccessKey(),
            'SignatureMethod' => 'HMAC-SHA1',
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'SignatureVersion' => '1.0',
            'SignatureNonce' => md5(uniqid('', true)),
            'Action' => 'AssumeRole',
            'RoleArn' => (string) $options['role_arn'],
            'RoleSessionName' => (string) ($options['role_session_name'] ?? 'storage-session'),
            'DurationSeconds' => (string) ($options['duration_seconds'] ?? 3600),
        ];

        if (!empty($options['policy'])) {
            $params['Policy'] = is_string($options['policy']) ? $options['policy'] : json_encode($options['policy']);
        }

        $signature = $this->signStsRequest($params, $this->config->getSecretKey());
        $url = rtrim((string) ($options['endpoint'] ?? 'https://sts.aliyuncs.com'), '?') . '/?' . http_build_query($params) . '&Signature=' . rawurlencode($signature);
        $body = $this->httpGetJson($url);

        if (isset($body['Code'])) {
            throw new Exception((string) $body['Message']);
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function signStsRequest(array $params, string $secretKey): string
    {
        ksort($params);
        $canonicalizedQueryString = '';
        foreach ($params as $key => $value) {
            $canonicalizedQueryString .= '&' . $this->percentEncode((string) $key) . '=' . $this->percentEncode((string) $value);
        }

        $stringToSign = 'GET&%2F&' . $this->percentEncode(substr($canonicalizedQueryString, 1));

        return base64_encode(hash_hmac('sha1', $stringToSign, $secretKey . '&', true));
    }

    private function percentEncode(string $value): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], urlencode($value));
    }

    /**
     * @return array<string, mixed>
     */
    private function httpGetJson(string $url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Curl error: ' . $error);
        }

        curl_close($ch);
        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new Exception('返回结果无法解析');
        }

        return $data;
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
                'access_key_id' => $credentials['AccessKeyId'] ?? $credentials['access_key_id'] ?? null,
                'access_key_secret' => $credentials['AccessKeySecret'] ?? $credentials['access_key_secret'] ?? null,
                'session_token' => $credentials['SecurityToken'] ?? $credentials['session_token'] ?? null,
            ],
            'expiration' => $raw['Expiration'] ?? $raw['expiration'] ?? null,
            'expired_at' => isset($raw['Expiration']) ? strtotime((string) $raw['Expiration']) : (isset($raw['expired_at']) ? (int) $raw['expired_at'] : null),
            'bucket' => $this->config->getBucket(),
            'region' => $this->config->getOption('region'),
            'domain' => $this->config->getOption('domain'),
            'success' => true,
            'raw' => $raw,
        ];
    }
}
