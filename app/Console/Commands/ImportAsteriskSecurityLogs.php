<?php

namespace App\Console\Commands;

use App\Models\SipSecurityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportAsteriskSecurityLogs extends Command
{
    protected $signature = 'asterisk:import-security-logs 
                            {--file=/var/log/asterisk/messages : Path to Asterisk log file}
                            {--since= : Only import logs since this datetime (Y-m-d H:i:s)}
                            {--tail=1000 : Number of lines to read from end of file}
                            {--daemon : Run continuously, watching for new logs}';

    protected $description = 'Import security events from Asterisk log files';

    // Pattern to match Asterisk security log entries
    private const LOG_PATTERN = '/^\[(\w+\s+\d+\s+\d+:\d+:\d+)\]\s+(\w+)\[\d+\]:\s+res_pjsip\/pjsip_distributor\.c:\d+\s+log_failed_request:\s+Request\s+\'(\w+)\'\s+from\s+\'([^\']*)\'\s+failed\s+for\s+\'([^:]+):(\d+)\'\s+\(callid:\s+([^)]+)\)\s+-\s+(.+)$/';
    
    // Alternative pattern for other security messages
    private const SECURITY_PATTERN = '/^\[(\w+\s+\d+\s+\d+:\d+:\d+)\]\s+(\w+)\[\d+\]:\s+(.+security.+|.+failed.+|.+rejected.+|.+unauthorized.+)/i';

    private string $serverPublicIp;
    private array $processedCallIds = [];

    public function handle(): int
    {
        $this->serverPublicIp = $this->getServerPublicIp();
        $this->info("Server public IP: {$this->serverPublicIp}");

        if ($this->option('daemon')) {
            return $this->runDaemon();
        }

        return $this->importOnce();
    }

    private function importOnce(): int
    {
        $file = $this->option('file');
        $tail = (int) $this->option('tail');
        $since = $this->option('since') ? Carbon::parse($this->option('since')) : null;

        if (!file_exists($file)) {
            $this->error("Log file not found: {$file}");
            return 1;
        }

        $this->info("Reading last {$tail} lines from {$file}...");

        // Read last N lines efficiently
        $lines = $this->tailFile($file, $tail);
        
        $imported = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $result = $this->parseLine($line, $since);
            if ($result === true) {
                $imported++;
            } elseif ($result === false) {
                $skipped++;
            }
        }

        $this->info("Imported: {$imported}, Skipped: {$skipped}");
        return 0;
    }

    private function runDaemon(): int
    {
        $file = $this->option('file');
        
        if (!file_exists($file)) {
            $this->error("Log file not found: {$file}");
            return 1;
        }

        $this->info("Watching {$file} for new security events... (Ctrl+C to stop)");

        // Open file and seek to end
        $handle = fopen($file, 'r');
        fseek($handle, 0, SEEK_END);

        while (true) {
            $line = fgets($handle);
            
            if ($line !== false) {
                $this->parseLine(trim($line));
            } else {
                // Check if file was rotated
                clearstatcache();
                $currentInode = fileinode($file);
                $handleStat = fstat($handle);
                
                if ($currentInode !== $handleStat['ino']) {
                    $this->info("Log file rotated, reopening...");
                    fclose($handle);
                    $handle = fopen($file, 'r');
                }
                
                usleep(100000); // 100ms
            }
        }
    }

    private function parseLine(string $line, ?Carbon $since = null): ?bool
    {
        // Try main pattern first
        if (preg_match(self::LOG_PATTERN, $line, $matches)) {
            return $this->processSecurityEvent($matches, $since);
        }

        return null; // Line didn't match
    }

    private function processSecurityEvent(array $matches, ?Carbon $since = null): bool
    {
        [, $dateStr, $level, $method, $fromUri, $sourceIp, $sourcePort, $callId, $reason] = $matches;

        // Parse date (format: "Jan  4 15:43:54")
        $year = date('Y');
        $eventTime = Carbon::createFromFormat('M j H:i:s Y', "{$dateStr} {$year}");
        
        // Handle year rollover
        if ($eventTime->isFuture()) {
            $eventTime->subYear();
        }

        // Check since filter
        if ($since && $eventTime->lt($since)) {
            return false;
        }

        // Check if already processed (by call_id)
        if (isset($this->processedCallIds[$callId])) {
            return false;
        }

        // Check if exists in database
        $exists = SipSecurityLog::where('call_id', $callId)->exists();
        if ($exists) {
            $this->processedCallIds[$callId] = true;
            return false;
        }

        // Parse from URI to extract caller info
        $callerInfo = $this->parseFromUri($fromUri);

        // Determine status
        $status = SipSecurityLog::STATUS_REJECTED;
        if (stripos($reason, 'authenticate') !== false) {
            $status = SipSecurityLog::STATUS_FAILED;
        }

        // Create the log entry
        SipSecurityLog::create([
            'event_time' => $eventTime,
            'event_type' => strtoupper($method),
            'direction' => 'inbound',
            'source_ip' => $sourceIp,
            'source_port' => (int) $sourcePort,
            'destination_ip' => $this->serverPublicIp,
            'destination_port' => 5060,
            'from_uri' => $fromUri,
            'caller_id' => $callerInfo['number'],
            'caller_name' => $callerInfo['name'],
            'status' => $status,
            'reject_reason' => trim($reason),
            'call_id' => $callId,
            'sip_response_code' => $this->mapReasonToSipCode($reason),
        ]);

        $this->processedCallIds[$callId] = true;
        
        if ($this->option('daemon')) {
            $this->line("<fg=red>[SECURITY]</> {$eventTime->format('H:i:s')} {$method} from {$sourceIp}:{$sourcePort} - {$reason}");
        }

        return true;
    }

    private function parseFromUri(string $uri): array
    {
        $name = null;
        $number = null;

        // Pattern: "Name" <sip:number@host> or <sip:number@host>
        if (preg_match('/^"([^"]+)"\s*<sip:([^@>]+)/', $uri, $m)) {
            $name = $m[1];
            $number = $m[2];
        } elseif (preg_match('/<sip:([^@>]+)/', $uri, $m)) {
            $number = $m[1];
        } elseif (preg_match('/sip:([^@>]+)/', $uri, $m)) {
            $number = $m[1];
        }

        return ['name' => $name, 'number' => $number];
    }

    private function mapReasonToSipCode(string $reason): int
    {
        $reason = strtolower($reason);

        if (str_contains($reason, 'authenticate')) {
            return 401; // Unauthorized
        }
        if (str_contains($reason, 'forbidden')) {
            return 403;
        }
        if (str_contains($reason, 'not found') || str_contains($reason, 'no matching endpoint')) {
            return 404;
        }
        if (str_contains($reason, 'rejected')) {
            return 403;
        }

        return 400; // Bad Request (default)
    }

    private function getServerPublicIp(): string
    {
        // Try to get from system settings first
        $publicIp = DB::table('system_settings')
            ->where('key', 'asterisk_external_ip')
            ->value('value');

        if ($publicIp) {
            return $publicIp;
        }

        // Try to get from environment
        $publicIp = env('SERVER_PUBLIC_IP');
        if ($publicIp) {
            return $publicIp;
        }

        // Try to detect from external service
        $services = [
            'https://api.ipify.org',
            'https://ifconfig.me/ip',
            'https://icanhazip.com',
        ];

        foreach ($services as $service) {
            try {
                $ip = @file_get_contents($service, false, stream_context_create([
                    'http' => ['timeout' => 2]
                ]));
                if ($ip && filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    return trim($ip);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback to getting from hostname
        $hostname = gethostname();
        $ip = gethostbyname($hostname);
        if ($ip && $ip !== $hostname) {
            return $ip;
        }

        return '0.0.0.0';
    }

    private function tailFile(string $file, int $lines): array
    {
        $result = [];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            return $result;
        }

        // Seek to end
        fseek($handle, 0, SEEK_END);
        $pos = ftell($handle);
        $lineCount = 0;
        $buffer = '';

        while ($pos > 0 && $lineCount <= $lines) {
            $pos--;
            fseek($handle, $pos);
            $char = fgetc($handle);
            
            if ($char === "\n") {
                if ($buffer !== '') {
                    array_unshift($result, $buffer);
                    $lineCount++;
                    $buffer = '';
                }
            } else {
                $buffer = $char . $buffer;
            }
        }

        if ($buffer !== '' && $lineCount < $lines) {
            array_unshift($result, $buffer);
        }

        fclose($handle);
        return $result;
    }
}

