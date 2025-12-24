<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Services\Asterisk\PjsipRealtimeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class VerifyAsteriskCommand extends Command
{
    protected $signature = 'asterisk:verify 
                            {--fix : Attempt to fix minor issues}';

    protected $description = 'Verify Asterisk connectivity and configuration';

    public function __construct(
        protected PjsipRealtimeService $pjsipService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Verifying Asterisk Integration...');
        $this->newLine();

        $issues = [];
        $warnings = [];

        // 1. Check AMI connectivity
        $this->checkAmiConnectivity($issues);

        // 2. Check ARI connectivity
        $this->checkAriConnectivity($issues, $warnings);

        // 3. Verify PJSIP tables exist
        $this->checkPjsipTables($issues);

        // 4. Check PJSIP table data consistency
        $this->checkDataConsistency($issues, $warnings);

        // 5. Get registration stats
        $this->displayRegistrationStats();

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        
        if (empty($issues) && empty($warnings)) {
            $this->info('✓ All checks passed! Asterisk integration is healthy.');
            return 0;
        }

        if (!empty($warnings)) {
            $this->warn('Warnings:');
            foreach ($warnings as $warning) {
                $this->line("  ⚠ {$warning}");
            }
            $this->newLine();
        }

        if (!empty($issues)) {
            $this->error('Issues found:');
            foreach ($issues as $issue) {
                $this->line("  ✗ {$issue}");
            }
            return 1;
        }

        return 0;
    }

    protected function checkAmiConnectivity(array &$issues): void
    {
        $this->line('Checking AMI connectivity...');
        
        $host = config('asterisk.ami.host') ?: SystemSetting::get('ami_host', '127.0.0.1');
        $port = (int) (config('asterisk.ami.port') ?: SystemSetting::get('ami_port', 5038));
        $username = config('asterisk.ami.username') ?: SystemSetting::get('ami_username', 'admin');
        $secret = config('asterisk.ami.password') ?: SystemSetting::get('ami_secret', '');

        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if (!$socket) {
            $issues[] = "Cannot connect to AMI at {$host}:{$port} - {$errstr}";
            $this->error("  ✗ AMI: Connection failed ({$errstr})");
            return;
        }

        // Try to login
        stream_set_timeout($socket, 5);
        fgets($socket); // Read welcome

        // Send login
        $loginCmd = "Action: Login\r\nUsername: {$username}\r\nSecret: {$secret}\r\n\r\n";
        fwrite($socket, $loginCmd);

        // Read response
        $response = '';
        while (($line = fgets($socket)) !== false) {
            $response .= $line;
            if (trim($line) === '') {
                break;
            }
        }

        if (strpos($response, 'Success') !== false) {
            $this->info("  ✓ AMI: Connected and authenticated at {$host}:{$port}");
            
            // Logoff
            fwrite($socket, "Action: Logoff\r\n\r\n");
        } else {
            $issues[] = "AMI authentication failed - check credentials";
            $this->error("  ✗ AMI: Authentication failed");
        }

        fclose($socket);
    }

    protected function checkAriConnectivity(array &$issues, array &$warnings): void
    {
        $this->line('Checking ARI connectivity...');
        
        $host = config('asterisk.ari.host', '127.0.0.1');
        $port = (int) config('asterisk.ari.port', 8088);
        $username = config('asterisk.ari.username', 'admin');
        $password = config('asterisk.ari.password', '');
        $ssl = config('asterisk.ari.ssl', false);

        $protocol = $ssl ? 'https' : 'http';
        $url = "{$protocol}://{$host}:{$port}/ari/asterisk/info";

        try {
            $response = Http::timeout(5)
                ->withBasicAuth($username, $password)
                ->get($url);

            if ($response->successful()) {
                $info = $response->json();
                $version = $info['build']['version'] ?? 'Unknown';
                $this->info("  ✓ ARI: Connected (Asterisk {$version})");
            } else {
                $warnings[] = "ARI returned status {$response->status()} - check configuration";
                $this->warn("  ⚠ ARI: HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $warnings[] = "ARI not accessible at {$url} - " . $e->getMessage();
            $this->warn("  ⚠ ARI: Not accessible ({$e->getMessage()})");
        }
    }

    protected function checkPjsipTables(array &$issues): void
    {
        $this->line('Checking PJSIP realtime tables...');

        $tables = [
            'ps_endpoints',
            'ps_auths',
            'ps_aors',
            'ps_contacts',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $count = DB::table($table)->count();
                $this->info("  ✓ {$table}: {$count} rows");
            } else {
                $issues[] = "Table {$table} does not exist";
                $this->error("  ✗ {$table}: Missing");
            }
        }
    }

    protected function checkDataConsistency(array &$issues, array &$warnings): void
    {
        $this->line('Checking data consistency...');

        // Check for orphaned entries in ps_endpoints
        $orphanedEndpoints = DB::table('ps_endpoints')
            ->leftJoin('extensions', 'ps_endpoints.id', '=', 'extensions.extension')
            ->whereNull('extensions.extension')
            ->count();

        if ($orphanedEndpoints > 0) {
            $warnings[] = "{$orphanedEndpoints} orphaned endpoint(s) in ps_endpoints";
            $this->warn("  ⚠ Orphaned endpoints: {$orphanedEndpoints}");
            
            if ($this->option('fix')) {
                $this->info("    Fixing: Running sync to clean up...");
                $this->pjsipService->syncAll();
                $this->info("    ✓ Cleanup completed");
            }
        } else {
            $this->info("  ✓ No orphaned endpoints");
        }

        // Check for extensions missing from PJSIP tables
        $missingInPjsip = DB::table('extensions')
            ->where('is_active', true)
            ->leftJoin('ps_endpoints', 'extensions.extension', '=', 'ps_endpoints.id')
            ->whereNull('ps_endpoints.id')
            ->count();

        if ($missingInPjsip > 0) {
            $warnings[] = "{$missingInPjsip} active extension(s) missing from PJSIP tables";
            $this->warn("  ⚠ Missing from PJSIP: {$missingInPjsip}");
            
            if ($this->option('fix')) {
                $this->info("    Fixing: Running sync...");
                $this->pjsipService->syncAll();
                $this->info("    ✓ Sync completed");
            }
        } else {
            $this->info("  ✓ All active extensions synced to PJSIP");
        }

        // Check auth/aor consistency
        $endpointCount = DB::table('ps_endpoints')->count();
        $authCount = DB::table('ps_auths')->count();
        $aorCount = DB::table('ps_aors')->count();

        if ($endpointCount !== $authCount || $endpointCount !== $aorCount) {
            $warnings[] = "PJSIP table counts don't match (endpoints: {$endpointCount}, auths: {$authCount}, aors: {$aorCount})";
            $this->warn("  ⚠ Table counts mismatch - run asterisk:sync-extensions");
        } else {
            $this->info("  ✓ PJSIP tables are consistent");
        }
    }

    protected function displayRegistrationStats(): void
    {
        $this->newLine();
        $this->line('Registration Statistics:');

        $stats = $this->pjsipService->getStats();
        $registered = $this->pjsipService->getRegisteredEndpoints();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Endpoints', $stats['total_endpoints']],
                ['Currently Registered', $stats['total_registered']],
                ['Registration Rate', $stats['total_endpoints'] > 0 
                    ? round(($stats['total_registered'] / $stats['total_endpoints']) * 100, 1) . '%' 
                    : 'N/A'],
            ]
        );

        if (!empty($stats['endpoints_by_transport'])) {
            $this->newLine();
            $this->line('Endpoints by Transport:');
            $transportData = [];
            foreach ($stats['endpoints_by_transport'] as $transport => $count) {
                $transportData[] = [$transport ?: '(default)', $count];
            }
            $this->table(['Transport', 'Count'], $transportData);
        }

        if (!empty($registered)) {
            $this->newLine();
            $this->line('Currently Registered Endpoints:');
            $regData = [];
            foreach ($registered as $ep => $info) {
                $regData[] = [
                    $ep,
                    $info->via_addr ?? 'Unknown',
                    $info->user_agent ?? 'Unknown',
                ];
            }
            $this->table(['Endpoint', 'IP Address', 'User Agent'], $regData);
        }
    }
}

