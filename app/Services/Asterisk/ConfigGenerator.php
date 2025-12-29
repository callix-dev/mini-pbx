<?php

namespace App\Services\Asterisk;

use App\Models\Extension;
use App\Models\Queue;
use App\Models\Carrier;
use App\Models\Ivr;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class ConfigGenerator
{
    protected string $configPath;

    public function __construct()
    {
        $this->configPath = SystemSetting::get('asterisk_config_path', '/etc/asterisk', 'general');
    }

    public function generatePjsipEndpoints(): string
    {
        $config = "; Auto-generated PJSIP endpoints - Do not edit manually\n";
        $config .= "; Generated: " . now()->toDateTimeString() . "\n\n";

        $extensions = Extension::active()->get();

        foreach ($extensions as $extension) {
            $config .= $this->generatePjsipEndpoint($extension);
            $config .= "\n";
        }

        return $config;
    }

    protected function generatePjsipEndpoint(Extension $extension): string
    {
        $ext = $extension->extension;
        $callerid = $extension->caller_id_name 
            ? "\"{$extension->caller_id_name}\" <{$extension->caller_id_number}>"
            : $extension->name;

        return <<<EOF
[{$ext}](endpoint-internal)
type=endpoint
context={$extension->context}
callerid={$callerid}
auth={$ext}
aors={$ext}
webrtc=yes
dtls_auto_generate_cert=yes
media_encryption=dtls

[{$ext}]
type=auth
auth_type=userpass
username={$ext}
password={$extension->password}

[{$ext}]
type=aor
max_contacts=3
remove_existing=yes
qualify_frequency=30

EOF;
    }

    public function generateQueuesConfig(): string
    {
        $config = "; Auto-generated Queue configuration - Do not edit manually\n";
        $config .= "; Generated: " . now()->toDateTimeString() . "\n\n";

        $config .= "[general]\n";
        $config .= "persistentmembers=yes\n";
        $config .= "autofill=yes\n";
        $config .= "monitor-type=MixMonitor\n\n";

        $queues = Queue::active()->with(['members.extension', 'holdMusic'])->get();

        foreach ($queues as $queue) {
            $config .= $this->generateQueueConfig($queue);
            $config .= "\n";
        }

        return $config;
    }

    protected function generateQueueConfig(Queue $queue): string
    {
        $holdMusic = $queue->holdMusic?->directory_name ?? 'default';
        
        $config = "[{$queue->name}]\n";
        $config .= "strategy={$queue->strategy}\n";
        $config .= "timeout={$queue->timeout}\n";
        $config .= "retry={$queue->retry}\n";
        $config .= "wrapuptime={$queue->wrapuptime}\n";
        $config .= "maxlen={$queue->maxlen}\n";
        $config .= "weight={$queue->weight}\n";
        $config .= "musicclass={$holdMusic}\n";
        $config .= "announce-holdtime={$queue->announce_holdtime}\n";
        $config .= "announce-position={$queue->announce_position}\n";
        $config .= "joinempty=" . ($queue->joinempty ? 'yes' : 'no') . "\n";
        $config .= "leavewhenempty=" . ($queue->leavewhenempty ? 'yes' : 'no') . "\n";

        // Add members
        foreach ($queue->members->where('auto_login', true) as $member) {
            $ext = $member->extension->extension;
            $penalty = $member->penalty;
            $config .= "member => PJSIP/{$ext},{$penalty}\n";
        }

        return $config;
    }

    public function generatePjsipTrunks(): string
    {
        $config = "; Auto-generated PJSIP trunks - Do not edit manually\n";
        $config .= "; Generated: " . now()->toDateTimeString() . "\n\n";

        $carriers = Carrier::active()->get();

        foreach ($carriers as $carrier) {
            $config .= $this->generatePjsipTrunk($carrier);
            $config .= "\n";
        }

        return $config;
    }

    protected function generatePjsipTrunk(Carrier $carrier): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $carrier->name);
        $codecs = implode(',', $carrier->codecs ?? ['ulaw', 'alaw']);

        $config = "; Trunk: {$carrier->name}\n";
        
        if ($carrier->auth_type === 'registration') {
            $config .= "[{$name}]\n";
            $config .= "type=registration\n";
            $config .= "outbound_auth={$name}-auth\n";
            $config .= "server_uri=sip:{$carrier->host}:{$carrier->port}\n";
            $config .= "client_uri=sip:{$carrier->username}@{$carrier->host}\n";
            $config .= "retry_interval=60\n\n";

            $config .= "[{$name}-auth]\n";
            $config .= "type=auth\n";
            $config .= "auth_type=userpass\n";
            $config .= "username={$carrier->username}\n";
            $config .= "password={$carrier->password}\n\n";
        }

        $config .= "[{$name}]\n";
        $config .= "type=endpoint\n";
        $config .= "context={$carrier->context}\n";
        $config .= "disallow=all\n";
        $config .= "allow={$codecs}\n";
        $config .= "aors={$name}\n";
        
        if ($carrier->auth_type === 'registration') {
            $config .= "outbound_auth={$name}-auth\n";
        }
        
        if ($carrier->from_domain) {
            $config .= "from_domain={$carrier->from_domain}\n";
        }
        if ($carrier->from_user) {
            $config .= "from_user={$carrier->from_user}\n";
        }
        $config .= "\n";

        $config .= "[{$name}]\n";
        $config .= "type=aor\n";
        $config .= "contact=sip:{$carrier->host}:{$carrier->port}\n";
        $config .= "qualify_frequency=30\n\n";

        $config .= "[{$name}]\n";
        $config .= "type=identify\n";
        $config .= "endpoint={$name}\n";
        $config .= "match={$carrier->host}\n";

        return $config;
    }

    public function writeConfig(string $filename, string $content): bool
    {
        $path = rtrim($this->configPath, '/') . '/' . $filename;
        
        try {
            file_put_contents($path, $content);
            Log::info("Config file written: {$path}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to write config file {$path}: " . $e->getMessage());
            return false;
        }
    }

    public function regenerateAllConfigs(): array
    {
        $results = [];

        // PJSIP Endpoints
        $content = $this->generatePjsipEndpoints();
        $results['pjsip_endpoints.conf'] = $this->writeConfig('pjsip_endpoints.conf', $content);

        // PJSIP Trunks
        $content = $this->generatePjsipTrunks();
        $results['pjsip_trunks.conf'] = $this->writeConfig('pjsip_trunks.conf', $content);

        // Queues
        $content = $this->generateQueuesConfig();
        $results['queues.conf'] = $this->writeConfig('queues.conf', $content);

        return $results;
    }
}







