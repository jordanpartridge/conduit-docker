<?php

declare(strict_types=1);

namespace Jordanpartridge\ConduitDocker;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Jordanpartridge\ConduitDocker\Commands\DockerinitCommand;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Jordanpartridge\ConduitDocker\Commands\DockerinitCommand::class
            ]);
        }
    }
}