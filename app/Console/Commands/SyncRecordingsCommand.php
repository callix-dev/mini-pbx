<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncRecordingsCommand extends Command
{
    protected $signature = 'recordings:sync 
                            {--days=7 : Number of days to look back}
                            {--force : Re-check even if recording_path is set}
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Sync call recordings from Asterisk recording directory to call logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        
        $recordingDir = config('asterisk.recordings.path', '/var/spool/asterisk/monitor');
        
        $this->info("Syncing recordings from: {$recordingDir}");
        $this->info("Looking back {$days} days");
        
        if (!is_dir($recordingDir)) {
            $this->error("Recording directory does not exist: {$recordingDir}");
            $this->line('');
            $this->line('Options:');
            $this->line('1. Create a symlink from Laravel storage to Asterisk recordings:');
            $this->line("   ln -s {$recordingDir} " . storage_path('app/recordings'));
            $this->line('');
            $this->line('2. Configure ASTERISK_RECORDINGS_PATH in .env to point to the correct directory');
            return 1;
        }

        // Get call logs without recordings
        $query = CallLog::where('created_at', '>=', now()->subDays($days));
        
        if (!$force) {
            $query->whereNull('recording_path');
        }
        
        $callLogs = $query->get();
        
        $this->info("Found {$callLogs->count()} call logs to check");
        
        $updated = 0;
        $notFound = 0;
        
        $bar = $this->output->createProgressBar($callLogs->count());
        $bar->start();
        
        foreach ($callLogs as $callLog) {
            $bar->advance();
            
            $recording = $this->findRecording($callLog, $recordingDir);
            
            if ($recording) {
                if ($dryRun) {
                    $this->newLine();
                    $this->line("Would update: {$callLog->uniqueid} -> {$recording}");
                } else {
                    $callLog->update(['recording_path' => $recording]);
                }
                $updated++;
            } else {
                $notFound++;
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Results:");
        $this->line("  - Found recordings: {$updated}");
        $this->line("  - Not found: {$notFound}");
        
        if ($dryRun) {
            $this->warn("Dry run - no changes made");
        }
        
        return 0;
    }

    private function findRecording(CallLog $callLog, string $recordingDir): ?string
    {
        $uniqueId = $callLog->uniqueid;
        $linkedId = $callLog->linkedid;
        $startDate = $callLog->start_time ? Carbon::parse($callLog->start_time) : null;
        
        $formats = ['wav', 'mp3', 'gsm', 'wav49'];
        
        // Search patterns to try
        $patterns = [];
        
        // Pattern 1: Direct filename with UniqueID
        foreach ($formats as $format) {
            $patterns[] = "{$uniqueId}.{$format}";
            $patterns[] = "{$linkedId}.{$format}";
        }
        
        // Pattern 2: Date-based subdirectory
        if ($startDate) {
            $dateDir = $startDate->format('Y/m/d');
            foreach ($formats as $format) {
                $patterns[] = "{$dateDir}/{$uniqueId}.{$format}";
                $patterns[] = "{$dateDir}/{$linkedId}.{$format}";
            }
            
            // Pattern 3: YYYY-MM-DD format
            $dateDir2 = $startDate->format('Y-m-d');
            foreach ($formats as $format) {
                $patterns[] = "{$dateDir2}/{$uniqueId}.{$format}";
                $patterns[] = "{$dateDir2}/{$linkedId}.{$format}";
            }
        }
        
        // Pattern 4: Caller-based naming
        $caller = $callLog->caller_id;
        $callee = $callLog->callee_id;
        if ($startDate && $caller) {
            $timestamp = $startDate->format('YmdHis');
            foreach ($formats as $format) {
                $patterns[] = "{$caller}-{$timestamp}.{$format}";
                $patterns[] = "{$caller}-{$callee}-{$timestamp}.{$format}";
                if ($startDate) {
                    $dateDir = $startDate->format('Y/m/d');
                    $patterns[] = "{$dateDir}/{$caller}-{$timestamp}.{$format}";
                    $patterns[] = "{$dateDir}/{$caller}-{$callee}-{$timestamp}.{$format}";
                }
            }
        }
        
        // Try each pattern
        foreach ($patterns as $pattern) {
            $fullPath = rtrim($recordingDir, '/') . '/' . $pattern;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        // Pattern 5: Glob search by UniqueID
        foreach ($formats as $format) {
            $foundFiles = glob("{$recordingDir}/**/*{$uniqueId}*.{$format}");
            if (!empty($foundFiles)) {
                return $foundFiles[0];
            }
        }
        
        return null;
    }
}







