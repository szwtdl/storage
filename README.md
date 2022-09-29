# storage

[![Build Status](https://github.com/szwtdl/storage/actions/workflows/tests.yml/badge.svg)](https://github.com/szwtdl/storage/actions)
[![Latest Stable Version](https://poser.pugx.org/szwtdl/storage/v/stable)](https://packagist.org/packages/szwtdl/storage)
[![Total Downloads](https://poser.pugx.org/szwtdl/storage/downloads)](https://packagist.org/packages/szwtdl/storage)
[![Latest Unstable Version](https://poser.pugx.org/szwtdl/storage/v/unstable)](https://packagist.org/packages/szwtdl/storage)
[![License](https://poser.pugx.org/szwtdl/storage/license)](https://packagist.org/packages/szwtdl/storage)
[![Monthly Downloads](https://poser.pugx.org/szwtdl/storage/d/monthly)](https://packagist.org/packages/szwtdl/storage)

### 安装

```bash
composer require szwtdl/storage
```

### laravel安装

```bash
php artisan vendor:publish --provider="Szwtdl/Storage\ServiceProvider"
```

#### `.env` 配置文件

```bash
STORAGE_TYPE=tencent or aliyun
STORAGE_SECRET_ID=
STORAGE_SECRET_KEY=
STORAGE_REGION=ap-guangzhou
STORAGE_BUCKET=test-100000
STORAGE_DOMAIN=
```

#### laravel 使用教程

```bash
$object = "test/example.txt";
$content = "Hello OSS";
# 上传
app('upload')->upload($object,$content); 

#下载
app('upload')->download($object, './test.txt');

# 删除
app('upload')->delete($object);
```

### 初始化

```bash
require_once __DIR__ . '/vendor/autoload.php';
$options = [
    'storage_type' => 'aliyun',
    'storage_secret_id' => '',
    'storage_secret_key' => '',
    'storage_region' => '',
    'storage_bucket' => 'test',
    'storage_domain' => 'https://szwtdl.oss-cn-hangzhou.aliyuncs.com',
];
$upload = new \Szwtdl\Storage\Upload($options);

```

### 上传

```bash
$object = "test/example.txt";
$content = "Hello OSS";
try {
    $result = $upload->storage->upload($object, $content);
    dd($result);
} catch (Exception $e) {
    dd($e->getMessage());
}
```

### 下载

```bash
$object = "test/example.txt";
$path = './test/demo.txt'
try {
    $upload->storage->download($object, $path);
} catch (Exception $e) {
    dd($e->getMessage());
}
```

### 删除

```bash
$object = "test/example.txt";
try {
    $upload->storage->delete($object);
} catch (Exception $e) {
    dd($e->getMessage());
}
```