<?php

declare(strict_types=1);
/**
 * This file is part of szwtdl/storage
 * @link     https://www.szwtdl.cn
 * @contact  szpengjian@gmail.com
 * @license  https://github.com/szwtdl/storage/blob/master/LICENSE
 */

namespace Szwtdl;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Szwtdl\Service\IService;

class ServiceProvider extends BaseServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->setupConfig();
        $this->app->singleton(IService::class, function (): IService {
            $name = config('storage.default');
            $configs = config('storage.list');
            return Storage::driver($name, $configs[$name]);
        });
        $this->app->singleton(Storage::class, function () {
            return $this->app->make(IService::class);
        });
        $this->app->alias(IService::class, 'storage');
        $this->app->alias(Storage::class, 'upload');
    }

    public function boot()
    {
        View::composer('view', function () {
            Log::info('这里是日记信息: ');
        });
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
