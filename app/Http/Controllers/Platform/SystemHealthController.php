<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(): View
    {
        $health = [
            'asterisk' => $this->checkAsterisk(),
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
        ];

        $metrics = $this->getServerMetrics();
        $asterisk = $this->getAsteriskStats();
        $errors = $this->getRecentErrors();

        return view('platform.system-health.index', compact('health', 'metrics', 'asterisk', 'errors'));
    }

    private function checkAsterisk(): array
    {
        try {
            $host = SystemSetting::getValue('ami_host', '127.0.0.1');
            $port = (int) SystemSetting::getValue('ami_port', 5038);

            $socket = @fsockopen($host, $port, $errno, $errstr, 2);

            if ($socket) {
                fclose($socket);
                return ['connected' => true, 'message' => 'Connected'];
            }

            return ['connected' => false, 'message' => $errstr];
        } catch (\Exception $e) {
            return ['connected' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['connected' => true, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['connected' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['connected' => true, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['connected' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            // Check if Horizon is running by checking its status
            $status = Cache::get('horizon:status', 'paused');
            return [
                'running' => $status === 'running',
                'status' => $status,
            ];
        } catch (\Exception $e) {
            return ['running' => false, 'status' => 'unknown'];
        }
    }

    private function getServerMetrics(): array
    {
        $metrics = [
            'cpu' => 0,
            'memory_used' => 0,
            'memory_total' => 0,
            'disk_used' => 0,
            'disk_total' => 0,
        ];

        try {
            // CPU usage
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $cpuCores = (int) shell_exec('nproc 2>/dev/null') ?: 1;
                $metrics['cpu'] = round(($load[0] / $cpuCores) * 100, 1);
            }

            // Memory usage
            if (is_readable('/proc/meminfo')) {
                $memInfo = file_get_contents('/proc/meminfo');
                preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total);
                preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $available);

                if (!empty($total[1])) {
                    $metrics['memory_total'] = round($total[1] / 1024, 0);
                    $metrics['memory_used'] = round(($total[1] - ($available[1] ?? 0)) / 1024, 0);
                }
            }

            // Disk usage
            $diskTotal = disk_total_space('/');
            $diskFree = disk_free_space('/');

            if ($diskTotal !== false && $diskFree !== false) {
                $metrics['disk_total'] = round($diskTotal / (1024 * 1024 * 1024), 1);
                $metrics['disk_used'] = round(($diskTotal - $diskFree) / (1024 * 1024 * 1024), 1);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get server metrics', ['error' => $e->getMessage()]);
        }

        return $metrics;
    }

    private function getAsteriskStats(): array
    {
        $stats = [
            'active_calls' => 0,
            'active_channels' => 0,
            'registered_extensions' => 0,
            'uptime' => 'N/A',
        ];

        try {
            // Get registered extensions count from database
            $stats['registered_extensions'] = Extension::where('status', '!=', 'offline')->count();

            // In a real implementation, you would query AMI for these stats
            // For now, we'll use placeholder values
        } catch (\Exception $e) {
            Log::warning('Failed to get Asterisk stats', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    private function getRecentErrors(): array
    {
        $errors = [];

        try {
            $logFile = storage_path('logs/laravel.log');

            if (is_readable($logFile)) {
                $lines = array_slice(file($logFile), -100);

                foreach (array_reverse($lines) as $line) {
                    if (stripos($line, '.ERROR') !== false || stripos($line, '.CRITICAL') !== false) {
                        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                            $errors[] = [
                                'message' => trim(substr($line, 0, 200)),
                                'time' => $matches[1],
                            ];

                            if (count($errors) >= 5) {
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to read error logs', ['error' => $e->getMessage()]);
        }

        return $errors;
    }
}


