<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */
namespace Szwtdl\Storage;

use OSS\OssClient;
use Szwtdl\Storage\Contracts\InterfaceStorage;

class Aliyun implements InterfaceStorage
{
    public array $options;

    public OssClient $storage;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->storage = new OssClient($this->options['storage_secret_id'], $this->options['storage_secret_key'], $this->options['storage_region']);
    }

    public function upload($path, $contents)
    {
        return $this->storage->putObject($this->options['storage_bucket'], $path, $contents);
    }

    public function download($filename, $path)
    {
        return $this->storage->getObject($this->options['storage_bucket'], $filename, [
            OssClient::OSS_FILE_DOWNLOAD => $path,
        ]);
    }

    public function delete($filename)
    {
        return $this->storage->deleteObject($this->options['storage_bucket'], $filename);
    }
}
