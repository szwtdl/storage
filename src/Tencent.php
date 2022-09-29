<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */
namespace Szwtdl\Storage;

use Qcloud\Cos\Client;
use Szwtdl\Storage\Contracts\InterfaceStorage;

class Tencent implements InterfaceStorage
{
    public array $options;

    public Client $storage;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->storage = new Client([
            'region' => $this->options['storage_region'],
            'credentials' => [
                'secretId' => $this->options['storage_secret_id'],
                'secretKey' => $this->options['storage_secret_key'],
            ],
        ]);
    }

    public function upload($path, $contents)
    {
        return $this->storage->putObject(
            [
                'Bucket' => $this->options['storage_bucket'],
                'Key' => $path,
                'Body' => $contents, ]
        );
    }

    public function download($filename, $path)
    {
        return $this->storage->getObject(
            [
                'Bucket' => $this->options['storage_bucket'],
                'Key' => $filename,
                'SaveAs' => $path, ]
        );
    }

    public function delete($filename)
    {
        return $this->storage->deleteObject([
            'Bucket' => $this->options['storage_bucket'],
            'Key' => $filename,
        ]);
    }
}
