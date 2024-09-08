<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Storage;

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
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments)
    {
        return self::make($method, $arguments[0]);
    }

    private static function make(string $name, array $config)
    {
        $class = "Szwtdl\\Storage\\Service\\{$name}";
        if (class_exists($class)) {
            return new $class($config);
        }
        throw new \Exception("Error class {$class}");
    }
}
