<?php

declare(strict_types=1);

namespace PHPSTORM_META {

    override(\app(0), map([
        'storage' => \Szwtdl\Storage\Service\IService::class,
        'upload' => \Szwtdl\Storage\Service\IService::class,
    ]));

    override(\resolve(0), map([
        'storage' => \Szwtdl\Storage\Service\IService::class,
        'upload' => \Szwtdl\Storage\Service\IService::class,
    ]));

    override(\Szwtdl\Storage\Storage::driver(0), map([
        'aliyun' => \Szwtdl\Storage\Service\Aliyun::class,
        'tencent' => \Szwtdl\Storage\Service\Tencent::class,
        'qiniu' => \Szwtdl\Storage\Service\Qiniu::class,
    ]));
}
