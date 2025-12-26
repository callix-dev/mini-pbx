<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use App\Models\Extension;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestCdrCommand extends Command
{
    protected $signature = 'cdr:test {--create : Create a test CDR record}';
    protected $description = 'Test CDR functionality and show recent call logs';

    public function handle(): int
    {
        if ($this->option('create')) {
            return $this->createTestCdr();
        }

        return $this->showCdrStats();
    }

    private function createTestCdr(): int
    {
        $this->info('Creating test CDR record...');

        $extension = Extension::first();
        
        $cdr = CallLog::create([
            'uniqueid' => 'test-' . time(),
            'linkedid' => 'test-' . time(),
            'type' => 'internal',
            'direction' => 'internal',
            'caller_id' => $extension?->extension ?? '1001',
            'caller_name' => $extension?->name ?? 'Test User',
            'callee_id' => '1002',
            'callee_name' => 'Test Callee',
            'extension_id' => $extension?->id,
            'status' => 'answered',
            'start_time' => now()->subMinutes(5),
            'answer_time' => now()->subMinutes(5)->addSeconds(10),
            'end_time' => now(),
            'duration' => 290,
            'billable_duration' => 280,
            'hangup_cause' => 'ANSWERED',
        ]);

        $this->info("✓ Created test CDR with ID: {$cdr->id}");
        $this->info("  Uniqueid: {$cdr->uniqueid}");
        $this->info("  Caller: {$cdr->caller_id} ({$cdr->caller_name})");
        $this->info("  Callee: {$cdr->callee_id}");
        $this->info("  Duration: {$cdr->duration}s");

        return 0;
    }

    private function showCdrStats(): int
    {
        $this->info('Call Log Statistics');
        $this->line('==================');

        $total = CallLog::count();
        $today = CallLog::whereDate('start_time', today())->count();
        $answered = CallLog::where('status', 'answered')->count();
        $missed = CallLog::where('status', 'missed')->count();
        $inbound = CallLog::where('type', 'inbound')->count();
        $outbound = CallLog::where('type', 'outbound')->count();
        $internal = CallLog::where('type', 'internal')->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records', $total],
                ['Today\'s Calls', $today],
                ['Answered', $answered],
                ['Missed', $missed],
                ['Inbound', $inbound],
                ['Outbound', $outbound],
                ['Internal', $internal],
            ]
        );

        $this->newLine();
        $this->info('Last 10 Call Logs:');
        
        $recent = CallLog::latest('start_time')->limit(10)->get();
        
        if ($recent->isEmpty()) {
            $this->warn('No call logs found.');
            $this->newLine();
            $this->line('Possible reasons:');
            $this->line('  1. AMI Listener is not running (php artisan ami:listen)');
            $this->line('  2. Asterisk is not sending CDR events');
            $this->line('  3. No calls have been made yet');
            $this->newLine();
            $this->line('To enable CDR events in Asterisk:');
            $this->line('  1. Edit /etc/asterisk/cdr.conf:');
            $this->line('     [general]');
            $this->line('     enable=yes');
            $this->newLine();
            $this->line('  2. Edit /etc/asterisk/manager.conf, add to your AMI user:');
            $this->line('     read = system,call,log,verbose,command,agent,user,config,cdr');
            $this->line('     write = system,call,log,verbose,command,agent,user,config');
            $this->newLine();
            $this->line('  3. Reload Asterisk:');
            $this->line('     asterisk -rx "core reload"');
            $this->newLine();
            $this->line('To create a test record: php artisan cdr:test --create');
        } else {
            $this->table(
                ['ID', 'Time', 'Type', 'From', 'To', 'Status', 'Duration'],
                $recent->map(fn($log) => [
                    $log->id,
                    $log->start_time?->format('Y-m-d H:i:s'),
                    $log->type,
                    $log->caller_id,
                    $log->callee_id,
                    $log->status,
                    $log->duration . 's',
                ])
            );
        }

        return 0;
    }
}


