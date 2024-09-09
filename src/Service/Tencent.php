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
use Szwtdl\Storage\Exception\InvalidArgumentException;

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
        } catch (Exception|InvalidArgumentException $e) {
            throw new Exception("初始化错误:".$e->getMessage());
        }
    }

    public function listBuckets(): array
    {
        try {
            $result = $this->client->listBuckets();
            $bucketList = $result['Buckets'][0]['Bucket'];
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
        } catch (Exception $e) {
            throw new Exception("获取失败:".$e->getMessage());
        }
    }

    public function createBucket(string $name, array $options = array()): object
    {
        try {
            return $this->client->createBucket(array('Bucket' => $name));
        } catch (Exception $e) {
            throw new Exception("创建失败:".$e->getMessage());
        }
    }

    public function deleteBucket(string $name): object
    {
        try {
            return $this->client->deleteBucket(array('Bucket' => $name));
        } catch (Exception $e) {
            throw new Exception("删除失败".$e->getMessage());
        }
    }

    public function listObj(array $options = [])
    {
        try {
            $delimiter = empty($options['delimiter']) ? '' : $options['delimiter'];
            $marker = empty($options['marker']) ? '' : $options['marker'];
            $prefix = empty($options['prefix']) ? '' : $options['prefix'];
            $MaxKeys = empty($options['max_keys']) ? 100 : $options['max_keys'];
            $result = $this->client->listObjects([
                'Bucket' => $this->getBucket(), // 存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                'Delimiter' => $delimiter, // Delimiter表示分隔符, 设置为/表示列出当前目录下的object, 设置为空表示列出所有的object
                'EncodingType' => 'url', // 编码格式，对应请求中的 encoding-type 参数
                'Marker' => $marker, // 起始对象键标记
                'Prefix' => $prefix, // Prefix表示列出的object的key以prefix开始
                'MaxKeys' => $MaxKeys, // 设置最大遍历出多少个对象, 一次listObjects最大支持1000
            ]);
            // 请求成功
            print_r($result);
        } catch (Exception $e) {
            throw new Exception("获取失败".$e->getMessage());
        }
    }

    public function upload(string $filePath, string $object)
    {
        try {
            return $this->client->upload(
                $this->getBucket(),
                $object,
                fopen($filePath, 'rb')
            );
        } catch (Exception $e) {
            throw new Exception("上传失败:".$e->getMessage());
        }
    }

    public function delete(string $object): object
    {
        try {
            return $this->client->deleteObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object,
            ]);
        } catch (Exception $e) {
            throw new Exception("删除失败:".$e->getMessage());
        }
    }

    public function download(string $object, string $filePath, array $options = []): object
    {
        try {
            return $this->client->getObject([
                'Bucket' => $this->getBucket(),
                'Key' => $object,
                'SaveAs' => $filePath,
            ]);
        } catch (Exception $e) {
            throw new Exception("下载失败:".$e->getMessage());
        }
    }

    private function getBucket(): string
    {
        return $this->config->getBucket() . '-' . $this->config->getEndpoint();
    }
}
