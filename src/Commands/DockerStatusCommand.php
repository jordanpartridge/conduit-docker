<?php

namespace JordanPartridge\ConduitDocker\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\table;

class DockerStatusCommand extends Command
{
    protected $signature = 'docker:status {--environment=development : Environment to check}';

    protected $description = 'Check Docker build and deployment status';

    public function handle(): int
    {
        $environment = $this->option('environment');

        $this->info("📊 Checking Docker status for {$environment} environment...");
        $this->newLine();

        // Check if build is running
        $buildStatus = $this->checkBuildStatus();
        
        // Check container status
        $containerStatus = $this->checkContainerStatus($environment);
        
        // Check build logs
        $this->checkBuildLogs();

        // Display status table
        $this->displayStatusTable($buildStatus, $containerStatus);

        return Command::SUCCESS;
    }

    protected function checkBuildStatus(): array
    {
        // Check if docker-compose build process is running
        $process = new Process(['pgrep', '-f', 'docker-compose']);
        $process->run();

        if ($process->isSuccessful()) {
            return [
                'status' => 'building',
                'message' => 'Docker build in progress',
                'icon' => '🔄'
            ];
        }

        // Check if build completed by looking for containers
        $containerCheck = new Process(['docker', 'ps', '-q']);
        $containerCheck->run();

        if ($containerCheck->isSuccessful() && !empty(trim($containerCheck->getOutput()))) {
            return [
                'status' => 'completed',
                'message' => 'Build completed, containers running',
                'icon' => '✅'
            ];
        }

        return [
            'status' => 'not_started',
            'message' => 'No build process detected',
            'icon' => '⭕'
        ];
    }

    protected function checkContainerStatus(string $environment): array
    {
        $composeFile = "docker/docker-compose.{$environment}.yml";
        
        if (!file_exists(getcwd() . '/' . $composeFile)) {
            return [
                'containers' => [],
                'message' => 'Docker compose file not found'
            ];
        }

        // Get container status
        $process = new Process([
            'docker-compose', 
            '-f', $composeFile, 
            'ps', 
            '--format', 'table'
        ], getcwd());
        
        $process->run();

        if ($process->isSuccessful()) {
            return [
                'containers' => $this->parseContainerOutput($process->getOutput()),
                'message' => 'Container status retrieved'
            ];
        }

        return [
            'containers' => [],
            'message' => 'Failed to get container status'
        ];
    }

    protected function parseContainerOutput(string $output): array
    {
        $lines = explode("\n", trim($output));
        $containers = [];

        foreach ($lines as $line) {
            if (empty(trim($line)) || strpos($line, 'Name') !== false) {
                continue;
            }

            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 3) {
                $containers[] = [
                    'name' => $parts[0] ?? 'unknown',
                    'status' => $parts[2] ?? 'unknown',
                    'ports' => $parts[5] ?? 'none'
                ];
            }
        }

        return $containers;
    }

    protected function checkBuildLogs(): void
    {
        $logFile = getcwd() . '/docker-build.log';
        
        if (file_exists($logFile)) {
            $this->info("📄 Recent build log (last 10 lines):");
            $process = new Process(['tail', '-10', $logFile]);
            $process->run();
            
            if ($process->isSuccessful()) {
                $this->line($process->getOutput());
            }
        } else {
            $this->line("📄 No build log found");
        }
    }

    protected function displayStatusTable(array $buildStatus, array $containerStatus): void
    {
        // Build status
        $this->info("🔧 Build Status:");
        $this->line($buildStatus['icon'] . ' ' . $buildStatus['message']);
        $this->newLine();

        // Container status
        if (!empty($containerStatus['containers'])) {
            $this->info("🐳 Container Status:");
            
            $tableData = [];
            foreach ($containerStatus['containers'] as $container) {
                $status = strpos($container['status'], 'Up') !== false ? '✅ Running' : '❌ Stopped';
                $tableData[] = [
                    $container['name'],
                    $status,
                    $container['ports']
                ];
            }

            table(['Container', 'Status', 'Ports'], $tableData);
        } else {
            $this->warn("🐳 No containers found");
        }

        // Next steps
        $this->newLine();
        $this->info("🎯 Available commands:");
        $this->line("• conduit docker:status - Refresh status");
        $this->line("• conduit docker:health - Run health checks");
        $this->line("• docker-compose logs -f - View live logs");
        $this->line("• docker ps - View all containers");
    }
}