<?php

namespace App\Console\Commands;

use App\Models\Carrier;
use App\Services\Asterisk\PjsipCarrierSyncService;
use Illuminate\Console\Command;

class SyncCarriersCommand extends Command
{
    protected $signature = 'carriers:sync {--id= : Sync a specific carrier by ID} {--all : Sync all carriers}';

    protected $description = 'Sync carriers to Asterisk PJSIP tables';

    public function __construct(
        protected PjsipCarrierSyncService $syncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('id')) {
            return $this->syncSingle((int) $this->option('id'));
        }

        if ($this->option('all')) {
            return $this->syncAll();
        }

        // Show carriers and prompt
        $carriers = Carrier::all();
        
        if ($carriers->isEmpty()) {
            $this->warn('No carriers found in database.');
            return Command::SUCCESS;
        }

        $this->info('Carriers in database:');
        $this->table(
            ['ID', 'Name', 'Type', 'Host', 'Auth Type', 'Active', 'Synced'],
            $carriers->map(fn($c) => [
                $c->id,
                $c->name,
                $c->type,
                $c->host,
                $c->auth_type,
                $c->is_active ? 'Yes' : 'No',
                $this->syncService->isCarrierSynced($c) ? 'Yes' : 'No',
            ])
        );

        if ($this->confirm('Sync all carriers to Asterisk?')) {
            return $this->syncAll();
        }

        return Command::SUCCESS;
    }

    protected function syncSingle(int $id): int
    {
        $carrier = Carrier::find($id);

        if (!$carrier) {
            $this->error("Carrier with ID {$id} not found.");
            return Command::FAILURE;
        }

        $this->info("Syncing carrier: {$carrier->name}");

        try {
            $this->syncService->syncCarrier($carrier);
            $this->info("✓ Carrier synced successfully!");
            
            // Show what was created
            $this->showSyncedEntities($carrier);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to sync carrier: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function syncAll(): int
    {
        $carriers = Carrier::all();
        $success = 0;
        $failed = 0;

        foreach ($carriers as $carrier) {
            $this->info("Syncing: {$carrier->name}...");
            
            try {
                $this->syncService->syncCarrier($carrier);
                $this->info("  ✓ Synced");
                $success++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Sync complete: {$success} succeeded, {$failed} failed");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function showSyncedEntities(Carrier $carrier): void
    {
        $endpointName = $carrier->getPjsipEndpointName();

        $this->newLine();
        $this->info('Created PJSIP entities:');

        // Check endpoint
        $endpoint = \DB::table('ps_endpoints')->where('id', $endpointName)->first();
        if ($endpoint) {
            $this->line("  • Endpoint: {$endpointName}");
        }

        // Check auth
        $auth = \DB::table('ps_auths')->where('id', $endpointName)->first();
        if ($auth) {
            $this->line("  • Auth: {$endpointName}");
        }

        // Check AOR
        $aor = \DB::table('ps_aors')->where('id', $endpointName)->first();
        if ($aor) {
            $this->line("  • AOR: {$endpointName}");
        }

        // Check registration (for registration auth type)
        if ($carrier->auth_type === 'registration') {
            $reg = \DB::table('ps_registrations')->where('id', $endpointName)->first();
            if ($reg) {
                $this->line("  • Registration: {$endpointName}");
            }
        }

        // Check identify (for IP auth type)
        if ($carrier->auth_type === 'ip') {
            $identify = \DB::table('ps_endpoint_id_ips')->where('id', $endpointName)->first();
            if ($identify) {
                $this->line("  • Identify: {$endpointName}");
            }
        }
    }
}
