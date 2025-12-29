<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use Illuminate\Console\Command;

class AmiDiagnosticCommand extends Command
{
    protected $signature = 'ami:diagnose {--test-call : Wait and log events for a test call}';
    protected $description = 'Diagnose AMI connection and event receiving';

    private $socket;

    public function handle(): int
    {
        $this->info('=== AMI Diagnostic Tool ===');
        $this->newLine();

        // Get settings
        $settings = SettingsService::getAmiSettings();
        $host = $settings['host'];
        $port = $settings['port'];
        $username = $settings['username'];
        $secret = $settings['password'];

        $this->table(['Setting', 'Value'], [
            ['Host', $host],
            ['Port', $port],
            ['Username', $username],
            ['Password', $secret ? str_repeat('*', strlen($secret)) : '(empty)'],
        ]);

        // Step 1: Test connection
        $this->newLine();
        $this->info('Step 1: Testing TCP connection to AMI...');
        
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 10);
        
        if (!$this->socket) {
            $this->error("✗ Cannot connect to AMI at {$host}:{$port}");
            $this->error("  Error: {$errstr} (code: {$errno})");
            $this->newLine();
            $this->warn('Troubleshooting:');
            $this->line('  1. Check if Asterisk is running: systemctl status asterisk');
            $this->line('  2. Check AMI is enabled in /etc/asterisk/manager.conf');
            $this->line("  3. Check port {$port} is open: netstat -tlnp | grep {$port}");
            $this->line("  4. Check firewall: ufw status / iptables -L");
            return 1;
        }
        
        $this->info("✓ Connected to {$host}:{$port}");
        
        // Read welcome message
        stream_set_timeout($this->socket, 5);
        $welcome = fgets($this->socket);
        $this->line("  Server: " . trim($welcome));

        // Step 2: Test login
        $this->newLine();
        $this->info('Step 2: Testing AMI authentication...');
        
        $loginCmd = "Action: Login\r\nUsername: {$username}\r\nSecret: {$secret}\r\nEvents: on\r\n\r\n";
        fwrite($this->socket, $loginCmd);
        
        $response = $this->readResponse();
        
        if (($response['Response'] ?? '') !== 'Success') {
            $this->error('✗ Authentication failed');
            $this->error('  Message: ' . ($response['Message'] ?? 'Unknown error'));
            $this->newLine();
            $this->warn('Troubleshooting:');
            $this->line('  1. Check username/password in System Settings');
            $this->line('  2. Check /etc/asterisk/manager.conf has the correct user');
            $this->line('  3. Check permit/deny ACL allows your IP');
            fclose($this->socket);
            return 1;
        }
        
        $this->info('✓ Authentication successful');
        $this->line('  Message: ' . ($response['Message'] ?? 'Authenticated'));

        // Step 3: Check permissions
        $this->newLine();
        $this->info('Step 3: Checking AMI permissions...');
        
        // Send CoreShowChannels to test read permission
        fwrite($this->socket, "Action: CoreShowChannels\r\nActionID: test123\r\n\r\n");
        $response = $this->readResponse();
        
        if (($response['Response'] ?? '') === 'Success') {
            $this->info('✓ Read permissions OK');
        } else {
            $this->warn('⚠ May have limited read permissions');
        }

        // Step 4: Check for CDR permission
        $this->newLine();
        $this->info('Step 4: Checking CDR event permissions...');
        $this->line('  To receive CDR events, your AMI user needs "read = cdr" permission');
        $this->line('  Check /etc/asterisk/manager.conf');

        // Step 5: Wait for events if requested
        if ($this->option('test-call')) {
            $this->newLine();
            $this->info('Step 5: Waiting for events... (make a test call now)');
            $this->line('  Press Ctrl+C to stop');
            $this->newLine();

            stream_set_timeout($this->socket, 1);
            $eventCount = 0;
            $cdrReceived = false;
            $startTime = time();
            $timeout = 120; // 2 minutes

            while ((time() - $startTime) < $timeout) {
                $response = $this->readResponse();
                
                if ($response && isset($response['Event'])) {
                    $eventType = $response['Event'];
                    $eventCount++;
                    
                    // Color code important events
                    $color = match($eventType) {
                        'Newchannel' => 'blue',
                        'Hangup' => 'yellow',
                        'Cdr' => 'green',
                        'Bridge', 'BridgeCreate', 'BridgeEnter' => 'cyan',
                        'Dial', 'DialBegin', 'DialEnd' => 'magenta',
                        default => 'gray',
                    };
                    
                    if ($eventType === 'Cdr') {
                        $cdrReceived = true;
                        $this->info("✓ CDR EVENT RECEIVED!");
                        $this->table(['Field', 'Value'], collect($response)->map(fn($v, $k) => [$k, is_string($v) ? substr($v, 0, 50) : $v])->toArray());
                    } else {
                        $channel = $response['Channel'] ?? $response['Channel1'] ?? '';
                        $extra = match($eventType) {
                            'Newchannel' => "CallerID: " . ($response['CallerIDNum'] ?? 'N/A'),
                            'Hangup' => "Cause: " . ($response['Cause-txt'] ?? $response['Cause'] ?? 'N/A'),
                            'Dial', 'DialBegin' => "Dest: " . ($response['DestChannel'] ?? $response['Destination'] ?? 'N/A'),
                            default => '',
                        };
                        
                        $this->line("<fg={$color}>[{$eventType}]</> {$channel} {$extra}");
                    }
                }
                
                usleep(10000);
            }

            $this->newLine();
            $this->info("Received {$eventCount} events in {$timeout} seconds");
            
            if (!$cdrReceived) {
                $this->warn('⚠ No CDR event received. Possible causes:');
                $this->line('  1. AMI user missing "cdr" read permission');
                $this->line('  2. CDR not enabled in /etc/asterisk/cdr.conf');
                $this->line('  3. Call did not complete (hangup before answer)');
            }
        }

        // Cleanup
        fwrite($this->socket, "Action: Logoff\r\n\r\n");
        fclose($this->socket);

        $this->newLine();
        $this->info('=== Diagnostic Complete ===');
        
        return 0;
    }

    private function readResponse(): array
    {
        $response = [];
        $emptyLines = 0;
        
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 4096);
            
            if ($line === false) {
                break;
            }
            
            $line = trim($line);
            
            if ($line === '') {
                $emptyLines++;
                if ($emptyLines >= 1 && !empty($response)) {
                    break;
                }
                continue;
            }
            
            $emptyLines = 0;
            
            if (str_contains($line, ': ')) {
                [$key, $value] = explode(': ', $line, 2);
                $response[$key] = $value;
            }
        }
        
        return $response;
    }
}







