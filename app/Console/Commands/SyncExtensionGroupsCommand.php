<?php

namespace App\Console\Commands;

use App\Models\ExtensionGroup;
use App\Services\Asterisk\AsteriskQueueSyncService;
use Illuminate\Console\Command;

class SyncExtensionGroupsCommand extends Command
{
    protected $signature = 'extension-groups:sync 
        {--id= : Sync a specific extension group by ID}
        {--cleanup : Clean up orphaned queues}';

    protected $description = 'Sync extension groups to Asterisk queues';

    protected AsteriskQueueSyncService $syncService;

    public function __construct(AsteriskQueueSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle(): int
    {
        $groupId = $this->option('id');
        $cleanup = $this->option('cleanup');

        if ($groupId) {
            return $this->syncSingle($groupId);
        }

        return $this->syncAll($cleanup);
    }

    private function syncSingle(int $groupId): int
    {
        $group = ExtensionGroup::with('extensions')->find($groupId);

        if (!$group) {
            $this->error("Extension group with ID {$groupId} not found.");
            return 1;
        }

        $this->info("Syncing extension group: {$group->name} (ID: {$group->id})");

        try {
            $this->syncService->syncExtensionGroup($group);
            $this->info("✓ Successfully synced to queue: extgroup_{$group->id}");
            $this->line("  Strategy: {$group->ring_strategy}");
            $this->line("  Ring Time: {$group->ring_time}s");
            $this->line("  Members: {$group->extensions->count()}");
        } catch (\Exception $e) {
            $this->error("✗ Failed to sync: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function syncAll(bool $cleanup): int
    {
        $groups = ExtensionGroup::with('extensions')->active()->get();

        if ($groups->isEmpty()) {
            $this->warn('No active extension groups found.');
            return 0;
        }

        $this->info("Syncing {$groups->count()} extension group(s) to Asterisk queues...");
        $this->newLine();

        $success = 0;
        $failed = 0;

        $this->withProgressBar($groups, function ($group) use (&$success, &$failed) {
            try {
                $this->syncService->syncExtensionGroup($group);
                $success++;
            } catch (\Exception $e) {
                $failed++;
            }
        });

        $this->newLine(2);

        if ($success > 0) {
            $this->info("✓ Successfully synced: {$success} group(s)");
        }

        if ($failed > 0) {
            $this->warn("✗ Failed to sync: {$failed} group(s)");
        }

        // Show summary table
        $this->newLine();
        $this->table(
            ['ID', 'Name', 'Strategy', 'Ring Time', 'Members', 'Queue Name'],
            $groups->map(fn($g) => [
                $g->id,
                $g->name,
                $g->ring_strategy,
                $g->ring_time . 's',
                $g->extensions->count(),
                'extgroup_' . $g->id,
            ])->toArray()
        );

        if ($cleanup) {
            $this->newLine();
            $this->info('Cleaning up orphaned queues...');
            // Cleanup is part of syncAllExtensionGroups but we already did individual syncs
            // So we just do cleanup here
            try {
                $this->syncService->syncAllExtensionGroups();
                $this->info('✓ Cleanup completed.');
            } catch (\Exception $e) {
                $this->warn("Cleanup warning: {$e->getMessage()}");
            }
        }

        return $failed > 0 ? 1 : 0;
    }
}

