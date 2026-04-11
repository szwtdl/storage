<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Service;

use Szwtdl\Exception\InvalidArgumentException;

class Config
{
    protected string $access_key;

    protected string $secret_key;

    protected string $bucket;

    protected string $endpoint;

    protected array $options;

    public function __construct(array $config)
    {
        if (
            empty($config['access_key'])
            || empty($config['secret_key'])
            || empty($config['bucket'])
            || empty($config['endpoint'])
        ) {
            throw new InvalidArgumentException("配置参数错误");
        }

        $this->access_key = $config['access_key'];
        $this->secret_key = $config['secret_key'];
        $this->bucket = $config['bucket'];
        $this->endpoint = $config['endpoint'];
        $this->options = isset($config['options']) && is_array($config['options']) ? $config['options'] : [];
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

    public function getAccessKey(): string
    {
        return $this->access_key;
    }

    public function getSecretKey(): string
    {
        return $this->secret_key;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }
}
