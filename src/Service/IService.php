<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Storage\Service;

/**
 * 统一对象存储服务接口。
 *
 * @phpstan-type BucketItem array{
 *     name: string,
 *     region?: string|null,
 *     class?: string|null,
 *     domain?: string|null,
 *     created_at?: string|null
 * }
 * @phpstan-type BucketResult array{
 *     name: string,
 *     status: string,
 *     success: bool,
 *     raw: mixed
 * }
 * @phpstan-type ObjectItem array{
 *     path: string,
 *     name: string,
 *     size: int,
 *     type: string,
 *     etag?: string|null,
 *     hash?: string|null,
 *     url?: string|null,
 *     last_modified?: string|null
 * }
 * @phpstan-type ListObjectOptions array{
 *     prefix?: string,
 *     delimiter?: string,
 *     marker?: string,
 *     max?: int,
 *     max_keys?: int,
 *     limit?: int
 * }
 * @phpstan-type UploadResult array{
 *     path: string,
 *     name: string,
 *     size: int,
 *     url?: string|null,
 *     etag?: string|null,
 *     hash?: string|null,
 *     success: bool,
 *     raw: mixed
 * }
 * @phpstan-type DeleteResult array{
 *     path: string,
 *     success: bool,
 *     raw: mixed
 * }
 * @phpstan-type DownloadResult array{
 *     path: string,
 *     save_as: string,
 *     size: int,
 *     url?: string|null,
 *     success: bool,
 *     raw: mixed
 * }
 * @phpstan-type TemporaryCredentialsResult array{
 *     type: string,
 *     credentials: array<string, mixed>,
 *     expiration?: string|null,
 *     expired_at?: int|null,
 *     bucket?: string|null,
 *     region?: string|null,
 *     domain?: string|null,
 *     success: bool,
 *     raw: mixed
 * }
 */
interface IService
{
    /**
     * 获取临时密钥或临时上传凭证。
     *
     * @param array<string, mixed> $options 厂商自定义参数
     * @return TemporaryCredentialsResult
     */
    public function getTemporaryCredentials(array $options = array()): array;

    /**
     * 获取存储桶列表。
     *
     * @return BucketItem[]
     */
    public function listBuckets(): array;

    /**
     * 创建存储桶。
     *
     * `options` 为厂商扩展参数，例如 `region`。
     *
     * @param string $name 存储桶名称
     * @param array<string, mixed> $options 厂商自定义参数
     * @return BucketResult
     */
    public function createBucket(string $name, array $options = array()): array;

    /**
     * 删除存储桶。
     *
     * @param string $name 存储桶名称
     * @return BucketResult
     */
    public function deleteBucket(string $name): array;

    /**
     * 获取对象列表。
     *
     * 不传参数时，默认返回当前 bucket 下的文件列表。
     * 传入 `prefix` 可筛选目录，传入 `delimiter` 可按目录层级列出。
     *
     * @param ListObjectOptions $options 查询参数
     * @return ObjectItem[]
     */
    public function listObj(array $options = array()): array;

    /**
     * 上传本地文件到对象存储。
     *
     * @param string $filePath 本地文件绝对路径或相对路径
     * @param string $object 远端对象路径，例如 `demo/logo.png`
     * @return UploadResult
     */
    public function upload(string $filePath, string $object): array;

    /**
     * 删除对象文件。
     *
     * @param string $object 远端对象路径
     * @return DeleteResult
     */
    public function delete(string $object): array;

    /**
     * 下载对象文件到本地。
     *
     * @param string $object 远端对象路径
     * @param string $filePath 本地保存路径
     * @param array<string, mixed> $options 厂商自定义下载参数
     * @return DownloadResult
     */
    public function download(string $object, string $filePath, array $options = array()): array;
}
