<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Szwtdl\Storage\Service\IService;
use Szwtdl\Storage\Storage;

class StorageTest extends TestCase
{
    public function testDriverReturnsServiceImplementation(): void
    {
        $service = Storage::driver('qiniu', [
            'access_key' => 'ak',
            'secret_key' => 'sk',
            'bucket' => 'demo',
            'endpoint' => 'z0',
            'options' => [
                'region' => 'z0',
                'domain' => 'https://cdn.example.com',
            ],
        ]);

        $this->assertInstanceOf(IService::class, $service);
    }
}
