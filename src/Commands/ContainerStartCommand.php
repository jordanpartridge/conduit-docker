<?php

namespace JordanPartridge\ConduitDocker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ContainerStartCommand extends Command
{
    protected $signature = 'docker:container:start';

    protected $description = 'Start the Laravel application in a Docker container';

    public function handle(): int
    {
        $this->info('🐳 Starting Laravel application in container...');

        // Wait for database connection
        $this->info('📡 Waiting for database connection...');
        if (! $this->waitForDatabase()) {
            $this->error('❌ Database connection failed');

            return Command::FAILURE;
        }
        $this->info('✅ Database connected!');

        // Run migrations
        $this->info('🔄 Running database migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        // Production optimizations
        if (app()->environment('production')) {
            $this->info('⚡ Optimizing for production...');
            $this->optimizeForProduction();
        }

        $this->info('🚀 Application started successfully!');

        // Keep the process running for supervisor
        $this->info('👀 Application is running and monitoring...');

        return Command::SUCCESS;
    }

    protected function waitForDatabase(int $maxAttempts = 30): bool
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                DB::connection()->getPdo();

                return true;
            } catch (\Exception $e) {
                $this->line("Database not ready (attempt {$attempt}/{$maxAttempts}), waiting...");
                sleep(2);
            }
        }

        return false;
    }

    protected function optimizeForProduction(): void
    {
        $commands = [
            'config:cache' => 'Caching configuration',
            'route:cache' => 'Caching routes',
            'view:cache' => 'Caching views',
        ];

        foreach ($commands as $command => $description) {
            $this->line("  - {$description}...");
            Artisan::call($command);
        }
    }
}
