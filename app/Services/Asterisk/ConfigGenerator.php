<?php

namespace App\Services\Asterisk;

use App\Models\Extension;
use App\Models\ExtensionGroup;
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

    /**
     * Generate Extension Groups dialplan configuration
     */
    public function generateExtensionGroupsDialplan(): string
    {
        $config = "; Auto-generated Extension Groups Dialplan - Do not edit manually\n";
        $config .= "; Generated: " . now()->toDateTimeString() . "\n\n";

        $config .= "; ===========================================\n";
        $config .= "; Extension Groups Context\n";
        $config .= "; Dial *6XX to reach group XX\n";
        $config .= "; ===========================================\n";
        $config .= "[extension-groups]\n\n";

        $groups = ExtensionGroup::active()->with('extensions')->get();

        foreach ($groups as $group) {
            if ($group->group_number) {
                $config .= $this->generateGroupDialplan($group);
                $config .= "\n";
            }
        }

        // Add feature codes context
        $config .= $this->generateFeatureCodesContext();

        // Add pickup groups context  
        $config .= $this->generatePickupGroupsContext();

        return $config;
    }

    /**
     * Generate dialplan for a single extension group
     */
    protected function generateGroupDialplan(ExtensionGroup $group): string
    {
        $groupNum = $group->group_number;
        $ringTime = $group->ring_time;
        $strategy = $group->ring_strategy;
        $moh = $group->music_on_hold;
        $recordOpt = $group->record_calls ? 'MixMonitor(${UNIQUEID}-group-${EXTEN}.wav,ab)' : '';
        
        $extensions = $group->extensions()->where('is_active', true)->orderByPivot('priority')->get();
        
        if ($extensions->isEmpty()) {
            return "; Group {$groupNum} ({$group->name}) - No active extensions\n";
        }

        $config = "; Group {$groupNum}: {$group->name}\n";
        $config .= "; Strategy: {$strategy}, Ring Time: {$ringTime}s\n";
        
        // Direct dial to group number
        $config .= "exten => {$groupNum},1,NoOp(Calling Extension Group: {$group->name})\n";
        $config .= " same => n,Set(GROUP_NAME={$group->name})\n";
        $config .= " same => n,Set(GROUP_ID={$group->id})\n";
        
        if ($recordOpt) {
            $config .= " same => n,{$recordOpt}\n";
        }

        // Generate dial string based on strategy
        switch ($strategy) {
            case 'ringall':
                $dialString = $extensions->map(fn($e) => 'PJSIP/' . $e->extension)->implode('&');
                $config .= " same => n,Dial({$dialString},{$ringTime},tTkKm({$moh}))\n";
                break;
                
            case 'hunt':
            case 'memoryhunt':
                // Ring one at a time, in order
                foreach ($extensions as $index => $ext) {
                    $config .= " same => n,Dial(PJSIP/{$ext->extension},{$ringTime},tTkKm({$moh}))\n";
                }
                break;
                
            case 'leastrecent':
            case 'fewestcalls':
            case 'random':
                // Use Queue-like behavior with Dial
                // For proper implementation, these should use the Queue app
                // Simplified: random order dial
                $dialString = $extensions->shuffle()->map(fn($e) => 'PJSIP/' . $e->extension)->implode('&');
                $config .= " same => n,Dial({$dialString},{$ringTime},tTkKm({$moh}))\n";
                break;
                
            case 'rrmemory':
                // Round Robin with memory - requires DB to track
                $config .= " same => n,Set(RRCOUNT=\${DB(group/{$group->id}/rrcount)})\n";
                $config .= " same => n,Set(RRCOUNT=\$[\${IF(\${RRCOUNT}?\${RRCOUNT}:0)} + 1])\n";
                $config .= " same => n,Set(DB(group/{$group->id}/rrcount)=\${RRCOUNT})\n";
                $count = $extensions->count();
                $config .= " same => n,Set(RRINDEX=\$[\${RRCOUNT} % {$count}])\n";
                foreach ($extensions as $index => $ext) {
                    $config .= " same => n,GotoIf(\$[\${RRINDEX} = {$index}]?dial{$index})\n";
                }
                foreach ($extensions as $index => $ext) {
                    $config .= " same => n(dial{$index}),Dial(PJSIP/{$ext->extension},{$ringTime},tTkKm({$moh}))\n";
                    if ($index < $extensions->count() - 1) {
                        $config .= " same => n,Goto(dial" . ($index + 1) . ")\n";
                    }
                }
                break;
                
            default:
                $dialString = $extensions->map(fn($e) => 'PJSIP/' . $e->extension)->implode('&');
                $config .= " same => n,Dial({$dialString},{$ringTime},tTkKm({$moh}))\n";
        }

        // Handle no answer - timeout destination
        if ($group->timeout_destination_type && $group->timeout_destination_id) {
            $config .= $this->generateDestinationGoto($group->timeout_destination_type, $group->timeout_destination_id, 'timeout');
        } else {
            $config .= " same => n,Voicemail(\${CALLERID(num)},u)\n";
        }
        
        $config .= " same => n,Hangup()\n";

        // Also add *6XX pattern for feature code dialing
        $config .= "\nexten => *6{$groupNum},1,Goto({$groupNum},1)\n";

        return $config;
    }

    /**
     * Generate feature codes dialplan context
     */
    protected function generateFeatureCodesContext(): string
    {
        $config = "\n; ===========================================\n";
        $config .= "; Feature Codes Context\n";
        $config .= "; ===========================================\n";
        $config .= "[feature-codes]\n\n";

        // Call Pickup - *8 picks up any ringing call in same pickup group
        $config .= "; Call Pickup (*8)\n";
        $config .= "exten => *8,1,NoOp(Call Pickup)\n";
        $config .= " same => n,Set(PICKUPGROUP=\${CHANNEL(pickupgroup)})\n";
        $config .= " same => n,Pickup()\n";
        $config .= " same => n,Hangup()\n\n";

        // Directed Call Pickup - *8XXX picks up extension XXX
        $config .= "; Directed Call Pickup (*8 + extension)\n";
        $config .= "exten => _*8XXXX,1,NoOp(Directed Pickup for \${EXTEN:2})\n";
        $config .= " same => n,Pickup(PJSIP/\${EXTEN:2}@PICKUPMARK)\n";
        $config .= " same => n,Hangup()\n\n";

        // Group pickup - *88 + group number
        $config .= "; Group Pickup (*88 + group number)\n";
        $config .= "exten => _*88X.,1,NoOp(Group Pickup for group \${EXTEN:3})\n";
        $config .= " same => n,Set(PICKUPGROUP=\${EXTEN:3})\n";
        $config .= " same => n,Pickup(@\${PICKUPGROUP})\n";
        $config .= " same => n,Hangup()\n\n";

        // DND Toggle - *78 enable, *79 disable
        $config .= "; Do Not Disturb (*78 on, *79 off)\n";
        $config .= "exten => *78,1,NoOp(DND Enable)\n";
        $config .= " same => n,Set(DB(DND/\${CALLERID(num)})=1)\n";
        $config .= " same => n,Playback(do-not-disturb&activated)\n";
        $config .= " same => n,Hangup()\n\n";
        
        $config .= "exten => *79,1,NoOp(DND Disable)\n";
        $config .= " same => n,Set(DB_DELETE(DND/\${CALLERID(num)})=)\n";
        $config .= " same => n,Playback(do-not-disturb&de-activated)\n";
        $config .= " same => n,Hangup()\n\n";

        // Call Forward All - *72XXX to set, *73 to cancel
        $config .= "; Call Forward All (*72 + number to set, *73 to cancel)\n";
        $config .= "exten => _*72.,1,NoOp(Call Forward All to \${EXTEN:3})\n";
        $config .= " same => n,Set(DB(CF/\${CALLERID(num)})=\${EXTEN:3})\n";
        $config .= " same => n,Playback(call-fwd-unconditional&for&extension)\n";
        $config .= " same => n,SayDigits(\${EXTEN:3})\n";
        $config .= " same => n,Playback(activated)\n";
        $config .= " same => n,Hangup()\n\n";
        
        $config .= "exten => *73,1,NoOp(Call Forward All Cancel)\n";
        $config .= " same => n,Set(DB_DELETE(CF/\${CALLERID(num)})=)\n";
        $config .= " same => n,Playback(call-fwd-unconditional&de-activated)\n";
        $config .= " same => n,Hangup()\n\n";

        return $config;
    }

    /**
     * Generate pickup groups configuration
     */
    protected function generatePickupGroupsContext(): string
    {
        $config = "\n; ===========================================\n";
        $config .= "; Pickup Groups Configuration\n";
        $config .= "; Add this to your endpoint template or individual endpoints\n";
        $config .= "; ===========================================\n\n";

        $groups = ExtensionGroup::active()->whereNotNull('pickup_group')->with('extensions')->get();

        if ($groups->isEmpty()) {
            $config .= "; No pickup groups configured\n";
            return $config;
        }

        // Generate PJSIP endpoint settings for pickup groups
        $config .= "; PJSIP Endpoint pickup settings (add to pjsip_endpoints.conf)\n";
        
        foreach ($groups as $group) {
            $config .= "\n; Pickup Group {$group->pickup_group}: {$group->name}\n";
            foreach ($group->extensions as $ext) {
                $config .= "; [{$ext->extension}]\n";
                $config .= "; pickup_group={$group->pickup_group}\n";
                $config .= "; named_pickup_group={$group->pickup_group}\n";
            }
        }

        return $config;
    }

    /**
     * Generate goto destination for timeout/failover
     */
    protected function generateDestinationGoto(string $type, int $id, string $label = ''): string
    {
        $prefix = $label ? " same => n({$label})," : " same => n,";
        
        switch ($type) {
            case 'extension':
                $ext = Extension::find($id);
                return $ext 
                    ? "{$prefix}Goto(from-internal,{$ext->extension},1)\n"
                    : "{$prefix}Hangup()\n";
                    
            case 'voicemail':
                $ext = Extension::find($id);
                return $ext 
                    ? "{$prefix}VoiceMail({$ext->extension},u)\n"
                    : "{$prefix}Hangup()\n";
                    
            case 'queue':
                $queue = Queue::find($id);
                return $queue 
                    ? "{$prefix}Goto(queues,{$queue->extension},1)\n"
                    : "{$prefix}Hangup()\n";
                    
            case 'hangup':
                return "{$prefix}Playback(goodbye)\n{$prefix}Hangup()\n";
                
            case 'external':
                return "{$prefix}Goto(outbound-routes,{$id},1)\n";
                
            default:
                return "{$prefix}Hangup()\n";
        }
    }

    /**
     * Generate internal dialplan that includes extension groups
     */
    public function generateInternalDialplan(): string
    {
        $config = "; Auto-generated Internal Dialplan - Do not edit manually\n";
        $config .= "; Generated: " . now()->toDateTimeString() . "\n\n";

        $config .= "[from-internal]\n";
        $config .= "; Include extension groups\n";
        $config .= "include => extension-groups\n";
        $config .= "include => feature-codes\n\n";

        // Internal extension dialing
        $config .= "; Internal Extension Dialing\n";
        $config .= "exten => _XXXX,1,NoOp(Internal Call to \${EXTEN})\n";
        $config .= " same => n,Set(CALLERID(name)=\${CALLERID(name)})\n";
        $config .= " same => n,GotoIf(\${DB_EXISTS(DND/\${EXTEN})}?dnd)\n";
        $config .= " same => n,GotoIf(\${DB_EXISTS(CF/\${EXTEN})}?cf)\n";
        $config .= " same => n,Set(__RECORDING=\${STRFTIME(\${EPOCH},,%Y/%m/%d)}/\${UNIQUEID}.wav)\n";
        $config .= " same => n,MixMonitor(\${RECORDING},ab)\n";
        $config .= " same => n,Dial(PJSIP/\${EXTEN},30,tTkKxX)\n";
        $config .= " same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"BUSY\"]?busy)\n";
        $config .= " same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"NOANSWER\"]?noanswer)\n";
        $config .= " same => n,Hangup()\n";
        $config .= " same => n(dnd),Playback(vm-extension)\n";
        $config .= " same => n,SayDigits(\${EXTEN})\n";
        $config .= " same => n,Playback(do-not-disturb)\n";
        $config .= " same => n,Hangup()\n";
        $config .= " same => n(cf),Set(FWDNUM=\${DB(CF/\${EXTEN})})\n";
        $config .= " same => n,Dial(PJSIP/\${FWDNUM},30,tTkK)\n";
        $config .= " same => n,Hangup()\n";
        $config .= " same => n(busy),VoiceMail(\${EXTEN},b)\n";
        $config .= " same => n,Hangup()\n";
        $config .= " same => n(noanswer),VoiceMail(\${EXTEN},u)\n";
        $config .= " same => n,Hangup()\n\n";

        return $config;
    }

    /**
     * Update PJSIP endpoint with pickup group settings
     */
    protected function generatePjsipEndpoint(Extension $extension): string
    {
        $ext = $extension->extension;
        $callerid = $extension->caller_id_name 
            ? "\"{$extension->caller_id_name}\" <{$extension->caller_id_number}>"
            : $extension->name;

        $pickupGroup = $extension->pickup_group ?? '';
        $pickupLine = $pickupGroup ? "pickup_group={$pickupGroup}\nnamed_pickup_group={$pickupGroup}\n" : '';

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
{$pickupLine}
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

        // Extension Groups Dialplan
        $content = $this->generateExtensionGroupsDialplan();
        $results['extensions_groups.conf'] = $this->writeConfig('extensions_groups.conf', $content);

        // Internal Dialplan
        $content = $this->generateInternalDialplan();
        $results['extensions_internal.conf'] = $this->writeConfig('extensions_internal.conf', $content);

        return $results;
    }

    /**
     * Reload Asterisk dialplan
     */
    public function reloadDialplan(): bool
    {
        try {
            exec('asterisk -rx "dialplan reload"', $output, $returnCode);
            Log::info('Asterisk dialplan reload', ['output' => $output, 'code' => $returnCode]);
            return $returnCode === 0;
        } catch (\Exception $e) {
            Log::error('Failed to reload Asterisk dialplan: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reload Asterisk PJSIP
     */
    public function reloadPjsip(): bool
    {
        try {
            exec('asterisk -rx "pjsip reload"', $output, $returnCode);
            Log::info('Asterisk PJSIP reload', ['output' => $output, 'code' => $returnCode]);
            return $returnCode === 0;
        } catch (\Exception $e) {
            Log::error('Failed to reload Asterisk PJSIP: ' . $e->getMessage());
            return false;
        }
    }
}





