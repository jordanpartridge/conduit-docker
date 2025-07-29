<?php

namespace JordanPartridge\ConduitDocker\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\table;

class HealthCheckCommand extends Command
{
    protected $signature = 'docker:health {--environment=development : Environment to check} {--format=table : Output format (table|json)}';

    protected $description = 'Run comprehensive health checks for the Laravel application';

    public function handle(): int
    {
        $environment = $this->option('environment');
        $format = $this->option('format');

        $this->info("🔍 Running health checks for {$environment} environment...");
        $this->newLine();

        $checks = [
            'Application' => $this->checkApplication(),
            'Database' => $this->checkDatabase(),
            'Redis' => $this->checkRedis(),
            'Storage' => $this->checkStorage(),
            'Container' => $this->checkContainer(),
        ];

        if ($format === 'json') {
            $this->line(json_encode($checks, JSON_PRETTY_PRINT));
        } else {
            $this->displayTable($checks);
        }

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');

        if ($allHealthy) {
            $this->info('✅ All health checks passed!');

            return Command::SUCCESS;
        } else {
            $this->error('❌ Some health checks failed');

            return Command::FAILURE;
        }
    }

    protected function checkApplication(): array
    {
        try {
            $response = file_get_contents(config('app.url').'/up');

            return [
                'status' => $response ? 'healthy' : 'unhealthy',
                'message' => $response ? 'Application responding' : 'Application not responding',
                'details' => ['url' => config('app.url').'/up'],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Application health check failed',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'details' => [
                    'driver' => config('database.default'),
                    'host' => config('database.connections.'.config('database.default').'.host'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    protected function checkRedis(): array
    {
        try {
            Redis::ping();

            return [
                'status' => 'healthy',
                'message' => 'Redis connection successful',
                'details' => [
                    'host' => config('database.redis.default.host'),
                    'port' => config('database.redis.default.port'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $testFile = 'health-check-'.time().'.txt';
            Storage::put($testFile, 'health check');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);

            return [
                'status' => $exists ? 'healthy' : 'unhealthy',
                'message' => $exists ? 'Storage access successful' : 'Storage access failed',
                'details' => ['disk' => config('filesystems.default')],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage check failed',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    protected function checkContainer(): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');

            return [
                'status' => 'healthy',
                'message' => 'Container metrics collected',
                'details' => [
                    'memory_usage' => $this->formatBytes($memoryUsage),
                    'memory_limit' => $memoryLimit,
                    'php_version' => PHP_VERSION,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Container check failed',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    protected function displayTable(array $checks): void
    {
        $tableData = [];

        foreach ($checks as $service => $check) {
            $status = $check['status'] === 'healthy' ? '✅ Healthy' : '❌ Unhealthy';

            $tableData[] = [
                $service,
                $status,
                $check['message'],
            ];
        }

        table(['Service', 'Status', 'Message'], $tableData);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }
}
