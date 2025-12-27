<?php

namespace App\Observers;

use App\Models\Extension;
use App\Services\Asterisk\PjsipRealtimeService;
use Illuminate\Support\Facades\Log;

/**
 * Observer for Extension model
 * 
 * Automatically syncs extension data to PJSIP realtime tables
 * when extensions are created, updated, or deleted.
 */
class ExtensionObserver
{
    protected PjsipRealtimeService $pjsipService;

    public function __construct(PjsipRealtimeService $pjsipService)
    {
        $this->pjsipService = $pjsipService;
    }

    /**
     * Handle the Extension "created" event.
     */
    public function created(Extension $extension): void
    {
        if ($extension->is_active) {
            $this->syncToPjsip($extension, 'created');
        }
    }

    /**
     * Handle the Extension "updated" event.
     */
    public function updated(Extension $extension): void
    {
        // Check if relevant fields changed
        $relevantFields = [
            'extension', 'name', 'password', 'context', 'is_active',
            'voicemail_enabled', 'caller_id_name', 'caller_id_number', 'settings'
        ];

        $changed = false;
        foreach ($relevantFields as $field) {
            if ($extension->wasChanged($field)) {
                $changed = true;
                break;
            }
        }

        // Check if extension number changed (requires special handling)
        if ($extension->wasChanged('extension')) {
            // Delete old endpoint first
            $oldExtension = $extension->getOriginal('extension');
            $this->pjsipService->deleteEndpoint($oldExtension);
            Log::info("PJSIP Observer: Deleted old endpoint {$oldExtension} (extension number changed)");
        }

        if ($changed) {
            $this->syncToPjsip($extension, 'updated');
        }
    }

    /**
     * Handle the Extension "deleted" event.
     */
    public function deleted(Extension $extension): void
    {
        $this->deletePjsip($extension, 'deleted');
    }

    /**
     * Handle the Extension "restored" event.
     */
    public function restored(Extension $extension): void
    {
        if ($extension->is_active) {
            $this->syncToPjsip($extension, 'restored');
        }
    }

    /**
     * Handle the Extension "force deleted" event.
     */
    public function forceDeleted(Extension $extension): void
    {
        $this->deletePjsip($extension, 'force deleted');
    }

    /**
     * Sync extension to PJSIP tables
     */
    protected function syncToPjsip(Extension $extension, string $event): void
    {
        try {
            $result = $this->pjsipService->syncEndpoint($extension);
            
            if ($result) {
                Log::info("PJSIP Observer: Synced extension {$extension->extension} ({$event})");
            } else {
                Log::warning("PJSIP Observer: Failed to sync extension {$extension->extension} ({$event})");
            }
        } catch (\Exception $e) {
            Log::error("PJSIP Observer: Exception syncing extension {$extension->extension} ({$event}): " . $e->getMessage());
        }
    }

    /**
     * Delete extension from PJSIP tables
     */
    protected function deletePjsip(Extension $extension, string $event): void
    {
        try {
            $result = $this->pjsipService->deleteEndpoint($extension->extension);
            
            if ($result) {
                Log::info("PJSIP Observer: Deleted endpoint {$extension->extension} ({$event})");
            } else {
                Log::warning("PJSIP Observer: Failed to delete endpoint {$extension->extension} ({$event})");
            }
        } catch (\Exception $e) {
            Log::error("PJSIP Observer: Exception deleting endpoint {$extension->extension} ({$event}): " . $e->getMessage());
        }
    }
}





