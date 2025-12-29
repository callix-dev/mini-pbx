<?php

namespace App\Observers;

use App\Models\ExtensionGroup;
use App\Services\Asterisk\AsteriskQueueSyncService;
use Illuminate\Support\Facades\Log;

class ExtensionGroupObserver
{
    protected AsteriskQueueSyncService $syncService;

    public function __construct(AsteriskQueueSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the ExtensionGroup "created" event.
     */
    public function created(ExtensionGroup $extensionGroup): void
    {
        $this->syncToAsterisk($extensionGroup);
    }

    /**
     * Handle the ExtensionGroup "updated" event.
     */
    public function updated(ExtensionGroup $extensionGroup): void
    {
        $this->syncToAsterisk($extensionGroup);
    }

    /**
     * Handle the ExtensionGroup "deleted" event.
     */
    public function deleted(ExtensionGroup $extensionGroup): void
    {
        try {
            $this->syncService->deleteExtensionGroupQueue($extensionGroup);
        } catch (\Exception $e) {
            Log::error('Failed to delete Asterisk queue for extension group', [
                'group_id' => $extensionGroup->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ExtensionGroup "restored" event.
     */
    public function restored(ExtensionGroup $extensionGroup): void
    {
        $this->syncToAsterisk($extensionGroup);
    }

    /**
     * Handle the ExtensionGroup "force deleted" event.
     */
    public function forceDeleted(ExtensionGroup $extensionGroup): void
    {
        try {
            $this->syncService->deleteExtensionGroupQueue($extensionGroup);
        } catch (\Exception $e) {
            Log::error('Failed to delete Asterisk queue for extension group', [
                'group_id' => $extensionGroup->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync extension group to Asterisk queue
     */
    protected function syncToAsterisk(ExtensionGroup $extensionGroup): void
    {
        // Only sync if group is active
        if (!$extensionGroup->is_active) {
            // If group became inactive, delete the queue
            try {
                $this->syncService->deleteExtensionGroupQueue($extensionGroup);
            } catch (\Exception $e) {
                // Ignore errors when deleting inactive group queue
            }
            return;
        }

        try {
            $this->syncService->syncExtensionGroup($extensionGroup);
        } catch (\Exception $e) {
            Log::error('Failed to sync extension group to Asterisk', [
                'group_id' => $extensionGroup->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

