<?php

namespace App\Console\Commands;

use App\Services\Asterisk\PjsipRealtimeService;
use Illuminate\Console\Command;

class SyncExtensionsCommand extends Command
{
    protected $signature = 'asterisk:sync-extensions 
                            {--force : Force sync even if already synced}
                            {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Sync all Laravel extensions to Asterisk PJSIP realtime tables';

    public function __construct(
        protected PjsipRealtimeService $pjsipService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting extension sync to Asterisk PJSIP tables...');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get current stats
        $stats = $this->pjsipService->getStats();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current PJSIP Endpoints', $stats['total_endpoints']],
                ['Currently Registered', $stats['total_registered']],
            ]
        );
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('Would sync extensions from database to PJSIP tables.');
            return 0;
        }

        // Perform sync
        $this->info('Syncing extensions...');
        
        $results = $this->pjsipService->syncAll();

        $this->newLine();
        $this->info('Sync completed!');
        $this->newLine();

        // Display results
        $this->table(
            ['Operation', 'Count'],
            [
                ['Extensions Synced', $results['synced']],
                ['Orphaned Endpoints Deleted', $results['deleted']],
                ['Failed Operations', $results['failed']],
            ]
        );

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        // Show new stats
        $this->newLine();
        $newStats = $this->pjsipService->getStats();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total PJSIP Endpoints', $newStats['total_endpoints']],
                ['Currently Registered', $newStats['total_registered']],
            ]
        );

        if ($results['failed'] > 0) {
            return 1;
        }

        return 0;
    }
}



