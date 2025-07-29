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

        // Run deployment steps
        $steps = [
            'validateConfiguration' => 'Validating configuration',
            'buildImages' => 'Building Docker images',
            'deployContainers' => 'Deploying containers',
            'runHealthChecks' => 'Running health checks',
        ];

        foreach ($steps as $method => $description) {
            $this->info("📋 {$description}...");

            if (! $this->$method($environment)) {
                $this->error("❌ {$description} failed");

                return Command::FAILURE;
            }

            $this->line("✅ {$description} completed");
        }

        $this->info('🎉 Deployment completed successfully!');

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
        $composeFile = base_path("docker/docker-compose.{$environment}.yml");

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
        ], base_path()); // Set working directory to project root

        $process->setTimeout(300);
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
        ], base_path()); // Set working directory to project root

        $process->setTimeout(180);
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

    protected function runHealthChecks(string $environment): bool
    {
        // Run the health check command
        $exitCode = $this->call('docker:health', ['--environment' => $environment]);

        return $exitCode === Command::SUCCESS;
    }
}
