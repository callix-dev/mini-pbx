<?php

namespace App\Console\Commands;

use App\Models\Extension;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResetStaleStatusCommand extends Command
{
    protected $signature = 'extensions:reset-stale {--force : Reset all on_call/ringing statuses}';
    protected $description = 'Reset stale extension statuses (on_call/ringing) that are no longer in active calls';

    public function handle(): int
    {
        $this->info('Checking for stale extension statuses...');

        // Get active calls from cache
        $activeCalls = Cache::get('active_calls', []);
        $extensionsInCall = [];
        
        foreach ($activeCalls as $call) {
            if (isset($call['caller_id'])) {
                $extensionsInCall[] = $call['caller_id'];
            }
            if (isset($call['destination'])) {
                $extensionsInCall[] = $call['destination'];
            }
        }

        $this->line("Active calls in cache: " . count($activeCalls));
        $this->line("Extensions in calls: " . implode(', ', $extensionsInCall) ?: 'None');

        // Get all extensions that show on_call or ringing
        $query = Extension::whereIn('status', ['on_call', 'ringing']);
        
        if (!$this->option('force')) {
            // Only reset if not in active calls
            $query->whereNotIn('extension', $extensionsInCall);
        }
        
        $staleExtensions = $query->get();

        if ($staleExtensions->isEmpty()) {
            $this->info('✓ No stale extension statuses found');
            return 0;
        }

        $this->warn("Found {$staleExtensions->count()} potentially stale extension(s):");
        
        $resetCount = 0;
        foreach ($staleExtensions as $ext) {
            // Check if registered in Asterisk
            $isRegistered = DB::table('ps_contacts')
                ->where('endpoint', $ext->extension)
                ->exists();
            
            $newStatus = $isRegistered ? 'online' : 'offline';
            
            $this->line("  - {$ext->extension} ({$ext->name}): {$ext->status} → {$newStatus}");
            
            $ext->status = $newStatus;
            $ext->saveQuietly();
            $resetCount++;
        }

        $this->info("✓ Reset {$resetCount} extension statuses");

        // Also clean up stale calls from cache
        $now = now();
        $cleanedCalls = collect($activeCalls)->filter(function ($call) use ($now) {
            if (!isset($call['cached_at'])) return false;
            $cachedAt = \Carbon\Carbon::parse($call['cached_at']);
            return $cachedAt->diffInMinutes($now) < 30; // Keep calls newer than 30 mins
        })->toArray();
        
        $removedCalls = count($activeCalls) - count($cleanedCalls);
        if ($removedCalls > 0) {
            Cache::put('active_calls', $cleanedCalls, now()->addHours(2));
            $this->info("✓ Removed {$removedCalls} stale calls from cache");
        }

        return 0;
    }
}


