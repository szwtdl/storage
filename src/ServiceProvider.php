<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */
namespace Szwtdl\Storage;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->setupConfig();
        $this->app->singleton(Upload::class, function () {
            $options = config('storage.default');
            $upload = new Upload($options);
            return $upload->storage;
        });
        $this->app->alias(Upload::class, 'upload');
    }

    public function provides()
    {
        return [Upload::class, 'upload'];
    }

    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config/storage.php');
        if ($this->app->runningInConsole()) {
            $this->publishes([$source => \config_path('storage.php')], 'storage');
        }
        $this->mergeConfigFrom($source, 'storage');
    }
}
