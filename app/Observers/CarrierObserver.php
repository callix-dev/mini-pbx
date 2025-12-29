<?php

namespace App\Observers;

use App\Models\Carrier;
use App\Services\Asterisk\PjsipCarrierSyncService;
use Illuminate\Support\Facades\Log;

class CarrierObserver
{
    protected PjsipCarrierSyncService $syncService;

    public function __construct(PjsipCarrierSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the Carrier "created" event.
     */
    public function created(Carrier $carrier): void
    {
        if ($carrier->is_active) {
            $this->syncToAsterisk($carrier);
        }
    }

    /**
     * Handle the Carrier "updated" event.
     */
    public function updated(Carrier $carrier): void
    {
        if ($carrier->is_active) {
            $this->syncToAsterisk($carrier);
        } else {
            // If carrier became inactive, remove from Asterisk
            $this->removeFromAsterisk($carrier);
        }
    }

    /**
     * Handle the Carrier "deleted" event.
     */
    public function deleted(Carrier $carrier): void
    {
        $this->removeFromAsterisk($carrier);
    }

    /**
     * Handle the Carrier "restored" event.
     */
    public function restored(Carrier $carrier): void
    {
        if ($carrier->is_active) {
            $this->syncToAsterisk($carrier);
        }
    }

    /**
     * Handle the Carrier "force deleted" event.
     */
    public function forceDeleted(Carrier $carrier): void
    {
        $this->removeFromAsterisk($carrier);
    }

    /**
     * Sync carrier to Asterisk
     */
    protected function syncToAsterisk(Carrier $carrier): void
    {
        try {
            $this->syncService->syncCarrier($carrier);
        } catch (\Exception $e) {
            Log::error('Failed to sync carrier to Asterisk', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove carrier from Asterisk
     */
    protected function removeFromAsterisk(Carrier $carrier): void
    {
        try {
            $this->syncService->deleteCarrier($carrier);
        } catch (\Exception $e) {
            Log::error('Failed to remove carrier from Asterisk', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

