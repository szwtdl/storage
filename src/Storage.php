<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl;

use Szwtdl\Exception\InvalidArgumentException;
use Szwtdl\Service\Aliyun;
use Szwtdl\Service\IService;
use Szwtdl\Service\Qiniu;
use Szwtdl\Service\Tencent;

/**
 * @mixin IService
 * @method static Aliyun aliyun(array $config)
 * @method static Aliyun Aliyun(array $config)
 * @method static Tencent tencent(array $config)
 * @method static Tencent Tencent(array $config)
 * @method static Qiniu qiniu(array $config)
 * @method static Qiniu Qiniu(array $config)
 */
class Storage
{
    /**
     * @param mixed $method
     * @param mixed $arguments
     * @return IService
     * @throws InvalidArgumentException
     */
    public static function __callStatic($method, $arguments)
    {
        return self::make($method, $arguments[0]);
    }

    /**
     * @return IService
     * @throws InvalidArgumentException
     */
    public function __call($method, $arguments)
    {
        return self::make($method, $arguments[0]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function driver(string $method, array $config): IService
    {
        return self::make($method, $config);
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function make(string $method, array $config): IService
    {
        $method = ucfirst($method);
        $class = "Szwtdl\\Service\\{$method}";
        if (class_exists($class)) {
            return new $class($config);
        }
        throw new InvalidArgumentException("没有找到{$method}服务");
    }
}
