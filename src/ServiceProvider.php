<?php

declare(strict_types=1);

namespace JordanPartridge\ConduitDocker;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use JordanPartridge\ConduitDocker\Commands\ContainerStartCommand;
use JordanPartridge\ConduitDocker\Commands\DeployCommand;
use JordanPartridge\ConduitDocker\Commands\DockerInitCommand;
use JordanPartridge\ConduitDocker\Commands\DockerStatusCommand;
use JordanPartridge\ConduitDocker\Commands\HealthCheckCommand;
use JordanPartridge\ConduitDocker\Commands\RollbackCommand;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/conduit-docker.php',
            'conduit-docker'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DockerInitCommand::class,
                DeployCommand::class,
                DockerStatusCommand::class,
                HealthCheckCommand::class,
                RollbackCommand::class,
                ContainerStartCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/conduit-docker.php' => config_path('conduit-docker.php'),
            ], 'conduit-docker-config');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('docker'),
            ], 'conduit-docker-stubs');
        }
    }
}
