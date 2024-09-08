### 对象存储

#### 安装
```bash
composer require szwtdl/storage
```

#### 使用
```php

$config = [
    'access_key' => '',
    'secret_key' => '',
    'bucket' => 'szwtdl',
    'endpoint' => 'http://oss-cn-qingdao.aliyuncs.com',
    'options' => [
        'domain' => 'https://szwtdl.oss-cn-qingdao.aliyuncs.com',
        'region' => 'oss-cn-qingdao',
    ],
];

$storage = Storage::Aliyun($config);
// $storage = Storage::Qiniu($config);
// $storage = Storage::Tencent($config);

$storage->upload("./example/example.txt", "demo/example.txt");

$storage->download("demo/example.txt", "./example/123456.txt");

$storage->delete("demo/example.txt");

```

#### laravel 使用
```php
php artisan vendor:publish --provider="Szwtdl\Storage\ServiceProvider"

$object = "test/example.txt";
$local_path = "./example/example.txt";

##### 上传
app('storage')->upload($local_path,$object);

##### 下载
app('storage')->upload($object,$$local_path);

#### 删除文件
app('storage')->upload($object);
```
