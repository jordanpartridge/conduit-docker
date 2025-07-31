<?php

namespace JordanPartridge\ConduitDocker\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DeployCommand extends Command
{
    protected $signature = 'docker:deploy {--environment=development : Environment to deploy to}';

    protected $description = 'Deploy Laravel application using Docker';

    public function handle(): int
    {
        $environment = $this->option('environment');

        $this->info("🚀 Starting deployment to {$environment} environment...");

        // Validate environment
        if (! $this->validateEnvironment($environment)) {
            return Command::FAILURE;
        }

        // Validate configuration
        $this->info("📋 Validating configuration...");
        if (! $this->validateConfiguration($environment)) {
            $this->error("❌ Configuration validation failed");
            return Command::FAILURE;
        }
        $this->line("✅ Configuration validated");

        // Warn about build time and start background build
        $this->warn("⚠️  Docker builds take 5-15 minutes due to PHP extension compilation.");
        $this->warn("⚠️  Starting build in background to avoid timeout issues.");
        $this->info("📋 Building Docker images in background...");

        if (! $this->startBackgroundBuild($environment)) {
            $this->error("❌ Failed to start Docker build");
            return Command::FAILURE;
        }

        $this->info("✅ Docker build started successfully!");
        $this->newLine();
        $this->info("📊 Build Status:");
        $this->line("• Build process: Running in background");
        $this->line("• Expected time: 5-15 minutes");
        $this->line("• Check progress: conduit docker:status");
        $this->line("• View logs: docker-compose logs -f");
        $this->newLine();
        $this->info("🎯 Next steps:");
        $this->line("1. Wait 5-15 minutes for build to complete");
        $this->line("2. Run: conduit docker:status");
        $this->line("3. Once built, containers will start automatically");

        return Command::SUCCESS;
    }

    protected function validateEnvironment(string $environment): bool
    {
        $allowedEnvs = ['development', 'staging', 'production'];

        if (! in_array($environment, $allowedEnvs)) {
            $this->error("Invalid environment: {$environment}");
            $this->line('Allowed environments: '.implode(', ', $allowedEnvs));

            return false;
        }

        return true;
    }

    protected function validateConfiguration(string $environment): bool
    {
        $projectRoot = getcwd();
        $composeFile = $projectRoot . "/docker/docker-compose.{$environment}.yml";

        if (! file_exists($composeFile)) {
            $this->error("Docker compose file not found: {$composeFile}");
            $this->line('Run: conduit docker:init --environment='.$environment);

            return false;
        }

        return true;
    }

    protected function buildImages(string $environment): bool
    {
        $composeFile = "docker/docker-compose.{$environment}.yml";

        $process = new Process([
            'docker-compose',
            '-f', $composeFile,
            'build',
            '--no-cache',
        ], getcwd()); // Set working directory to project root

        $process->setTimeout(900); // 15 minutes for PHP extension compilation
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Docker build failed:');
            $this->line($process->getErrorOutput());
            $this->line('Full output:');
            $this->line($process->getOutput());

            return false;
        }

        return true;
    }

    protected function deployContainers(string $environment): bool
    {
        $composeFile = "docker/docker-compose.{$environment}.yml";

        $process = new Process([
            'docker-compose',
            '-f', $composeFile,
            'up',
            '-d',
            '--remove-orphans',
        ], getcwd()); // Set working directory to project root

        $process->setTimeout(300); // 5 minutes for container startup
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Container deployment failed:');
            $this->line($process->getErrorOutput());

            return false;
        }

        // Give containers time to start
        sleep(5);

        return true;
    }

    protected function startBackgroundBuild(string $environment): bool
    {
        $composeFile = "docker/docker-compose.{$environment}.yml";

        // Start build in background with nohup
        $buildCommand = "nohup docker-compose -f {$composeFile} up --build -d > docker-build.log 2>&1 &";
        
        $process = new Process(['sh', '-c', $buildCommand], getcwd());
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Failed to start background build:');
            $this->line($process->getErrorOutput());
            return false;
        }

        // Give it a moment to start
        sleep(2);

        // Check if docker-compose process started
        $checkProcess = new Process(['pgrep', '-f', 'docker-compose']);
        $checkProcess->run();

        return $checkProcess->isSuccessful();
    }

    protected function runHealthChecks(string $environment): bool
    {
        // Run the health check command
        $exitCode = $this->call('docker:health', ['--environment' => $environment]);

        return $exitCode === Command::SUCCESS;
    }
}
