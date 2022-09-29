<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */
namespace Szwtdl\Storage\Contracts;

interface InterfaceStorage
{
    public function upload($path, $contents);

    public function download($filename, $path);

    public function delete($filename);
}
