<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl\Storage\Service;

interface IService
{
    public function listBuckets();

    public function createBucket(string $name, array $options = array());

    public function deleteBucket(string $name);

    public function listObj(array $options);

    public function upload(string $filePath, string $object);

    public function delete(string $object);

    public function download(string $object, string $filePath, array $options);
}
