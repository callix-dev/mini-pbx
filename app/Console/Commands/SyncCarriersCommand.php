<?php

namespace App\Console\Commands;

use App\Models\Carrier;
use App\Services\Asterisk\PjsipCarrierSyncService;
use Illuminate\Console\Command;

class SyncCarriersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carriers:sync 
                            {--id= : Sync a specific carrier by ID}
                            {--all : Sync all active carriers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync carriers to Asterisk PJSIP tables';

    /**
     * Execute the console command.
     */
    public function handle(PjsipCarrierSyncService $syncService): int
    {
        if ($this->option('id')) {
            return $this->syncSingle($syncService, $this->option('id'));
        }

        if ($this->option('all')) {
            return $this->syncAll($syncService);
        }

        $this->error('Please specify --id=<carrier_id> or --all');
        return Command::FAILURE;
    }

    private function syncSingle(PjsipCarrierSyncService $syncService, int $carrierId): int
    {
        $carrier = Carrier::find($carrierId);

        if (!$carrier) {
            $this->error("Carrier with ID {$carrierId} not found.");
            return Command::FAILURE;
        }

        $this->info("Syncing carrier: {$carrier->name}");

        try {
            $syncService->syncCarrier($carrier);
            $this->info("Successfully synced carrier: {$carrier->name}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to sync carrier: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function syncAll(PjsipCarrierSyncService $syncService): int
    {
        $carriers = Carrier::active()->get();

        if ($carriers->isEmpty()) {
            $this->warn('No active carriers to sync.');
            return Command::SUCCESS;
        }

        $this->info("Syncing {$carriers->count()} carriers...");
        $bar = $this->output->createProgressBar($carriers->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($carriers as $carrier) {
            try {
                $syncService->syncCarrier($carrier);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to sync {$carrier->name}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync complete: {$success} successful, {$failed} failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

