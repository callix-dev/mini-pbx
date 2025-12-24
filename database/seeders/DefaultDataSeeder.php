<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\BreakCode;
use App\Models\Disposition;
use App\Models\HoldMusic;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create Superadmin user
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@minipbx.local',
            'password' => Hash::make('password'),
            'is_active' => true,
            'agent_status' => 'offline',
            'timezone' => 'UTC',
        ]);
        $superadmin->assignRole('Superadmin');

        // Create default Break Codes
        $breakCodes = [
            ['name' => 'Lunch', 'code' => 'LUNCH', 'color' => '#f59e0b', 'is_paid' => false, 'sort_order' => 1],
            ['name' => 'Tea Break', 'code' => 'TEA', 'color' => '#10b981', 'is_paid' => true, 'sort_order' => 2],
            ['name' => 'Training', 'code' => 'TRAINING', 'color' => '#6366f1', 'is_paid' => true, 'sort_order' => 3],
            ['name' => 'Meeting', 'code' => 'MEETING', 'color' => '#8b5cf6', 'is_paid' => true, 'sort_order' => 4],
            ['name' => 'Personal', 'code' => 'PERSONAL', 'color' => '#ec4899', 'is_paid' => false, 'sort_order' => 5],
        ];

        foreach ($breakCodes as $code) {
            BreakCode::create(array_merge($code, ['is_active' => true]));
        }

        // Create default Dispositions
        $dispositions = [
            ['name' => 'Sold', 'code' => 'SOLD', 'color' => '#10b981', 'requires_callback' => false, 'sort_order' => 1],
            ['name' => 'Not Interested', 'code' => 'NI', 'color' => '#ef4444', 'requires_callback' => false, 'sort_order' => 2],
            ['name' => 'Callback Requested', 'code' => 'CB', 'color' => '#f59e0b', 'requires_callback' => true, 'sort_order' => 3],
            ['name' => 'No Answer', 'code' => 'NA', 'color' => '#6b7280', 'requires_callback' => true, 'sort_order' => 4],
            ['name' => 'Busy', 'code' => 'BUSY', 'color' => '#8b5cf6', 'requires_callback' => true, 'sort_order' => 5],
            ['name' => 'Wrong Number', 'code' => 'WN', 'color' => '#64748b', 'requires_callback' => false, 'sort_order' => 6],
            ['name' => 'Do Not Call', 'code' => 'DNC', 'color' => '#dc2626', 'requires_callback' => false, 'sort_order' => 7],
            ['name' => 'Voicemail', 'code' => 'VM', 'color' => '#0ea5e9', 'requires_callback' => true, 'sort_order' => 8],
            ['name' => 'Information Sent', 'code' => 'INFO', 'color' => '#14b8a6', 'requires_callback' => true, 'sort_order' => 9],
            ['name' => 'Other', 'code' => 'OTHER', 'color' => '#94a3b8', 'requires_callback' => false, 'is_default' => true, 'sort_order' => 99],
        ];

        foreach ($dispositions as $disp) {
            Disposition::create(array_merge($disp, ['is_active' => true]));
        }

        // Create default Hold Music class
        HoldMusic::create([
            'name' => 'Default',
            'description' => 'Default hold music class',
            'directory_name' => 'default',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create default System Settings
        $settings = [
            ['group' => 'ami', 'key' => 'host', 'value' => '127.0.0.1', 'type' => 'string'],
            ['group' => 'ami', 'key' => 'port', 'value' => '5038', 'type' => 'integer'],
            ['group' => 'ami', 'key' => 'username', 'value' => 'admin', 'type' => 'string'],
            ['group' => 'ami', 'key' => 'password', 'value' => '', 'type' => 'string', 'is_encrypted' => true],
            ['group' => 'ami', 'key' => 'enabled', 'value' => 'false', 'type' => 'boolean'],

            ['group' => 'ari', 'key' => 'host', 'value' => '127.0.0.1', 'type' => 'string'],
            ['group' => 'ari', 'key' => 'port', 'value' => '8088', 'type' => 'integer'],
            ['group' => 'ari', 'key' => 'username', 'value' => 'admin', 'type' => 'string'],
            ['group' => 'ari', 'key' => 'password', 'value' => '', 'type' => 'string', 'is_encrypted' => true],
            ['group' => 'ari', 'key' => 'enabled', 'value' => 'false', 'type' => 'boolean'],

            ['group' => 'general', 'key' => 'timezone', 'value' => 'UTC', 'type' => 'string'],
            ['group' => 'general', 'key' => 'asterisk_config_path', 'value' => '/etc/asterisk', 'type' => 'string'],
            ['group' => 'general', 'key' => 'asterisk_sounds_path', 'value' => '/var/lib/asterisk/sounds', 'type' => 'string'],
            ['group' => 'general', 'key' => 'asterisk_recording_path', 'value' => '/var/spool/asterisk/monitor', 'type' => 'string'],

            ['group' => 'webrtc', 'key' => 'stun_server', 'value' => 'stun:stun.l.google.com:19302', 'type' => 'string'],
            ['group' => 'webrtc', 'key' => 'turn_server', 'value' => '', 'type' => 'string'],
            ['group' => 'webrtc', 'key' => 'turn_username', 'value' => '', 'type' => 'string'],
            ['group' => 'webrtc', 'key' => 'turn_password', 'value' => '', 'type' => 'string', 'is_encrypted' => true],

            ['group' => 'retention', 'key' => 'enabled', 'value' => 'false', 'type' => 'boolean'],
            ['group' => 'retention', 'key' => 'days', 'value' => '365', 'type' => 'integer'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create(array_merge($setting, ['is_encrypted' => $setting['is_encrypted'] ?? false]));
        }
    }
}

