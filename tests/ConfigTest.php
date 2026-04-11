<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Szwtdl\Storage\Exception\InvalidArgumentException;
use Szwtdl\Storage\Service\Config;

class ConfigTest extends TestCase
{
    public function testConfigCanReadValuesAndDefaultOption(): void
    {
        $config = new Config([
            'access_key' => 'ak',
            'secret_key' => 'sk',
            'bucket' => 'demo',
            'endpoint' => 'oss-cn-qingdao',
            'options' => [
                'region' => 'oss-cn-qingdao',
            ],
        ]);

        $this->assertSame('ak', $config->getAccessKey());
        $this->assertSame('sk', $config->getSecretKey());
        $this->assertSame('demo', $config->getBucket());
        $this->assertSame('oss-cn-qingdao', $config->getEndpoint());
        $this->assertSame('oss-cn-qingdao', $config->getOption('region'));
        $this->assertSame('fallback', $config->getOption('domain', 'fallback'));
    }

    public function testConfigThrowsExceptionWhenRequiredFieldsAreMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config([
            'access_key' => '',
            'secret_key' => 'sk',
            'bucket' => 'demo',
            'endpoint' => 'oss-cn-qingdao',
        ]);
    }
}
