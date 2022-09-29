<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */
namespace Szwtdl\Storage;

use Szwtdl\Storage\Contracts\InterfaceStorage;

class Upload
{
    public InterfaceStorage $storage;

    public array $options = [
        'storage_type' => 'aliyun',
        'storage_secret_id' => '',
        'storage_secret_key' => '',
        'storage_region' => '',
        'storage_bucket' => '',
        'storage_domain' => '',
    ];

    public function __construct(array $options)
    {
        $this->options = array_merge($this->options, $options);
        if (in_array($this->options['storage_type'], ['aliyun', 'tencent'])) {
            $className = 'Szwtdl\\Storage\\' . ucfirst($this->options['storage_type']);
            if (! class_exists($className)) {
                throw new \Exception('Class Exists');
            }
            $this->storage = new $className($this->options);
        }
    }
}
