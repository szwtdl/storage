## 对象存储

统一封装阿里云 OSS、腾讯云 COS、七牛云 Kodo，所有驱动都实现 `Szwtdl\Storage\Service\IService`。

## 安装

```bash
composer require szwtdl/storage
```

## 基础配置

```php
use Szwtdl\Storage\Service\IService;
use Szwtdl\Storage\Storage;

$config = [
    'access_key' => 'your-access-key',
    'secret_key' => 'your-secret-key',
    'bucket' => 'demo-bucket',
    'endpoint' => 'oss-cn-qingdao',
    'options' => [
        'region' => 'oss-cn-qingdao',
        'domain' => 'https://cdn.example.com',
    ],
];

/** @var IService $storage */
$storage = Storage::driver('aliyun', $config);
// $storage = Storage::driver('tencent', $config);
// $storage = Storage::driver('qiniu', $config);
```

## 统一接口

```php
interface IService
{
    public function listBuckets(): array;

    public function createBucket(string $name, array $options = []): array;

    public function deleteBucket(string $name): array;

    public function listObj(array $options = []): array;

    public function upload(string $filePath, string $object): array;

    public function delete(string $object): array;

    public function download(string $object, string $filePath, array $options = []): array;
}
```

## 返回结构

### 存储桶列表 `listBuckets`

```php
[
    [
        'name' => 'demo-bucket',
        'region' => 'oss-cn-qingdao',
        'class' => 'Standard',
        'domain' => 'https://cdn.example.com',
        'created_at' => '2026-04-12 10:00:00',
    ],
]
```

### 对象列表 `listObj`

```php
[
    [
        'path' => 'demo/logo.png',
        'name' => 'logo.png',
        'size' => 12345,
        'type' => 'file',
        'etag' => 'etag-or-hash',
        'hash' => 'qiniu-hash-only',
        'url' => 'https://cdn.example.com/demo/logo.png',
        'last_modified' => '2026-04-12 10:00:00',
    ],
]
```

### 创建/删除存储桶

```php
[
    'name' => 'demo-bucket',
    'status' => 'created', // 或 deleted
    'success' => true,
    'raw' => $sdkResponse,
]
```

### 上传结果 `upload`

```php
[
    'path' => 'demo/logo.png',
    'name' => 'logo.png',
    'size' => 12345,
    'etag' => 'etag-or-hash',
    'hash' => 'qiniu-hash-only',
    'url' => 'https://cdn.example.com/demo/logo.png',
    'success' => true,
    'raw' => $sdkResponse,
]
```

### 删除结果 `delete`

```php
[
    'path' => 'demo/logo.png',
    'success' => true,
    'raw' => $sdkResponse,
]
```

### 下载结果 `download`

```php
[
    'path' => 'demo/logo.png',
    'save_as' => '/tmp/logo.png',
    'size' => 12345,
    'url' => 'https://cdn.example.com/demo/logo.png',
    'success' => true,
    'raw' => $sdkResponse,
]
```

## 接口示例

### 1. 创建服务实例

```php
use Szwtdl\Storage\Service\IService;
use Szwtdl\Storage\Storage;

/** @var IService $storage */
$storage = Storage::driver('aliyun', $config);
```

### 2. 获取存储桶列表

```php
$buckets = $storage->listBuckets();

print_r($buckets);
```

### 3. 创建存储桶

```php
$result = $storage->createBucket('demo-new-bucket', [
    'region' => 'oss-cn-qingdao',
]);

print_r($result);
```

### 4. 删除存储桶

```php
$result = $storage->deleteBucket('demo-new-bucket');

print_r($result);
```

### 5. 获取对象列表

不传参数时，默认返回当前 bucket 下文件列表：

```php
$list = $storage->listObj();

print_r($list);
```

按前缀筛选：

```php
$list = $storage->listObj([
    'prefix' => 'demo/',
]);

print_r($list);
```

按目录层级列出：

```php
$list = $storage->listObj([
    'prefix' => 'demo/',
    'delimiter' => '/',
]);

print_r($list);
```

如果配置了 `options.domain`，每一项都会包含完整访问地址：

```php
$list = $storage->listObj();

echo $list[0]['url'] ?? null;
```

### 6. 上传文件

```php
$result = $storage->upload('/absolute/path/logo.png', 'demo/logo.png');

echo $result['url'] ?? null;
print_r($result);
```

### 7. 删除文件

```php
$result = $storage->delete('demo/logo.png');

var_dump($result['success']);
```

### 8. 下载文件

```php
$result = $storage->download('demo/logo.png', '/tmp/logo.png');

echo $result['save_as'];
print_r($result);
```

## Laravel 使用

发布配置：

```php
php artisan vendor:publish --provider="Szwtdl\Storage\ServiceProvider"
```

使用容器：

```php
/** @var \Szwtdl\Storage\Service\IService $storage */
$storage = app('storage');

$storage->upload('/absolute/path/logo.png', 'demo/logo.png');
$storage->listObj();
$storage->download('demo/logo.png', '/tmp/logo.png');
$storage->delete('demo/logo.png');
```
