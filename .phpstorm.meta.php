<?php

declare(strict_types=1);

namespace PHPSTORM_META {

    override(\app(0), map([
        'storage' => \Szwtdl\Service\IService::class,
        'upload' => \Szwtdl\Service\IService::class,
    ]));

    override(\resolve(0), map([
        'storage' => \Szwtdl\Service\IService::class,
        'upload' => \Szwtdl\Service\IService::class,
    ]));

    override(\Szwtdl\Storage\Storage::driver(0), map([
        'aliyun' => \Szwtdl\Service\Aliyun::class,
        'tencent' => \Szwtdl\Service\Tencent::class,
        'qiniu' => \Szwtdl\Service\Qiniu::class,
    ]));
}
