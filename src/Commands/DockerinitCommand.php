<?php

namespace JordanPartridge\ConduitDocker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DockerInitCommand extends Command
{
    protected $signature = 'conduit:docker:init {--environment=development : Environment to initialize for}';
    protected $description = 'Initialize Docker configuration for Laravel application';

    public function handle(): int
    {
        $environment = $this->option('environment');
        
        $this->info("🐳 Initializing Docker configuration for {$environment} environment...");

        // Create docker directory if it doesn't exist
        if (!File::exists(base_path('docker'))) {
            File::makeDirectory(base_path('docker'), 0755, true);
            $this->info('📁 Created docker directory');
        }

        // Copy stubs based on environment
        $this->copyStubs($environment);
        
        // Update .env.example with Docker variables
        $this->updateEnvExample();
        
        // Validate Laravel configuration
        $this->validateConfiguration();

        $this->info('✅ Docker initialization complete!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Review and customize the generated Docker files');
        $this->line('2. Update your .env file with Docker settings');
        $this->line("3. Run: php artisan conduit:deploy --env={$environment}");

        return Command::SUCCESS;
    }

    protected function copyStubs(string $environment): void
    {
        $stubsPath = __DIR__ . '/../../stubs';
        
        $files = [
            'Dockerfile.production' => 'Dockerfile.production',
            'docker-compose.yml' => 'docker-compose.yml',
            "docker-compose.{$environment}.yml" => "docker-compose.{$environment}.yml",
            'nginx.conf' => 'nginx.conf',
            'supervisord.conf' => 'supervisord.conf',
            'php.ini' => 'php.ini',
            'start-container.sh' => 'start-container.sh',
        ];

        foreach ($files as $stub => $target) {
            $stubFile = "{$stubsPath}/{$stub}";
            $targetFile = base_path("docker/{$target}");
            
            if (File::exists($stubFile)) {
                File::copy($stubFile, $targetFile);
                File::chmod($targetFile, 0755);
                $this->line("✓ Created {$target}");
            }
        }
    }

    protected function updateEnvExample(): void
    {
        $envExamplePath = base_path('.env.example');
        
        if (!File::exists($envExamplePath)) {
            $this->warn('⚠️ .env.example not found, skipping Docker variables update');
            return;
        }

        $dockerVars = [
            '',
            '# Docker Configuration',
            'DOCKER_IMAGE_NAME=laravel-app',
            'DOCKER_REGISTRY=localhost:5000',
            'WWWUSER=1000',
            'WWWGROUP=1000',
            'APP_PORT=80',
            'FORWARD_DB_PORT=5432',
            'FORWARD_REDIS_PORT=6379',
        ];

        $content = File::get($envExamplePath);
        
        // Only add if not already present
        if (!str_contains($content, 'DOCKER_IMAGE_NAME')) {
            File::append($envExamplePath, implode("\n", $dockerVars));
            $this->line('✓ Updated .env.example with Docker variables');
        }
    }

    protected function validateConfiguration(): void
    {
        $issues = [];

        // Check for required directories
        $requiredDirs = ['storage/app', 'storage/logs', 'bootstrap/cache'];
        foreach ($requiredDirs as $dir) {
            if (!File::exists(base_path($dir))) {
                $issues[] = "Missing directory: {$dir}";
            }
        }

        // Check for common Laravel files
        $requiredFiles = ['artisan', 'composer.json', 'config/app.php'];
        foreach ($requiredFiles as $file) {
            if (!File::exists(base_path($file))) {
                $issues[] = "Missing file: {$file}";
            }
        }

        if (!empty($issues)) {
            $this->warn('⚠️ Configuration issues detected:');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
        } else {
            $this->line('✓ Laravel configuration validated');
        }
    }
}