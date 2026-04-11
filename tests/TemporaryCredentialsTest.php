<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Szwtdl\Service\Aliyun;
use Szwtdl\Service\Qiniu;
use Szwtdl\Service\Tencent;

class TemporaryCredentialsTest extends TestCase
{
    public function testAliyunCanNormalizeTemporaryCredentials(): void
    {
        $service = new Aliyun([
            'access_key' => 'ak',
            'secret_key' => 'sk',
            'bucket' => 'demo-bucket',
            'endpoint' => 'oss-cn-qingdao.aliyuncs.com',
            'options' => [
                'region' => 'oss-cn-qingdao',
                'domain' => 'https://cdn.example.com',
            ],
        ]);

        $result = $service->getTemporaryCredentials([
            'credentials' => [
                'access_key_id' => 'tmp-ak',
                'access_key_secret' => 'tmp-sk',
                'session_token' => 'tmp-token',
            ],
            'expiration' => '2026-04-12T12:00:00Z',
            'expired_at' => 1760000000,
        ]);

        $this->assertSame('sts', $result['type']);
        $this->assertTrue($result['success']);
        $this->assertSame('tmp-ak', $result['credentials']['access_key_id']);
        $this->assertSame('tmp-sk', $result['credentials']['access_key_secret']);
        $this->assertSame('tmp-token', $result['credentials']['session_token']);
        $this->assertSame('demo-bucket', $result['bucket']);
        $this->assertSame('oss-cn-qingdao', $result['region']);
        $this->assertSame('https://cdn.example.com', $result['domain']);
    }

    public function testTencentCanNormalizeTemporaryCredentials(): void
    {
        $service = new Tencent([
            'access_key' => 'ak',
            'secret_key' => 'sk',
            'bucket' => 'demo-bucket',
            'endpoint' => '1250000000',
            'options' => [
                'region' => 'ap-guangzhou',
                'domain' => 'https://cdn.example.com',
            ],
        ]);

        $result = $service->getTemporaryCredentials([
            'credentials' => [
                'access_key_id' => 'tmp-ak',
                'access_key_secret' => 'tmp-sk',
                'session_token' => 'tmp-token',
            ],
            'expiration' => '2026-04-12T12:00:00Z',
            'expired_at' => 1760000000,
        ]);

        $this->assertSame('sts', $result['type']);
        $this->assertTrue($result['success']);
        $this->assertSame('tmp-ak', $result['credentials']['access_key_id']);
        $this->assertSame('tmp-sk', $result['credentials']['access_key_secret']);
        $this->assertSame('tmp-token', $result['credentials']['session_token']);
        $this->assertSame('demo-bucket-1250000000', $result['bucket']);
        $this->assertSame('ap-guangzhou', $result['region']);
    }

    public function testQiniuCanGenerateUploadToken(): void
    {
        $service = new Qiniu([
            'access_key' => 'ak',
            'secret_key' => 'sk',
            'bucket' => 'demo-bucket',
            'endpoint' => 'z0',
            'options' => [
                'region' => 'z0',
                'domain' => 'https://cdn.example.com',
            ],
        ]);

        $result = $service->getTemporaryCredentials([
            'key' => 'demo/logo.png',
            'expires' => 3600,
        ]);

        $this->assertSame('upload_token', $result['type']);
        $this->assertTrue($result['success']);
        $this->assertSame('ak', $result['credentials']['access_key_id']);
        $this->assertArrayHasKey('upload_token', $result['credentials']);
        $this->assertNotEmpty($result['credentials']['upload_token']);
        $this->assertSame('demo-bucket', $result['bucket']);
        $this->assertSame('z0', $result['region']);
    }
}
