<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Storage;

use Szwtdl\Storage\Exception\InvalidArgumentException;

/**
 * @method object Aliyun(array $config)
 * @method object Tencent(array $config)
 * @method object Qiniu(array $config)
 */
class Storage
{
    /**
     * @param mixed $method
     * @param mixed $arguments
     * @throws InvalidArgumentException
     */
    public static function __callStatic($method, $arguments)
    {
        return self::make($method, $arguments[0]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __call($method, $arguments)
    {
        return self::make($method, $arguments[0]);
    }

    private static function make(string $method, array $config)
    {
        $method = ucfirst($method);
        $class = "Szwtdl\\Storage\\Service\\{$method}";
        if (class_exists($class)) {
            return new $class($config);
        }
        throw new InvalidArgumentException("没有找到{$method}服务");
    }
}
