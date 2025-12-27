<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'view-dashboard',
            'view-live-calls',
            'view-agent-status',
            'view-queue-stats',

            // Extensions
            'view-extensions',
            'create-extensions',
            'edit-extensions',
            'delete-extensions',

            // Extension Groups
            'view-extension-groups',
            'create-extension-groups',
            'edit-extension-groups',
            'delete-extension-groups',

            // DIDs
            'view-dids',
            'create-dids',
            'edit-dids',
            'delete-dids',

            // Queues
            'view-queues',
            'create-queues',
            'edit-queues',
            'delete-queues',
            'manage-queue-agents',

            // Ring Trees
            'view-ring-trees',
            'create-ring-trees',
            'edit-ring-trees',
            'delete-ring-trees',

            // IVRs
            'view-ivrs',
            'create-ivrs',
            'edit-ivrs',
            'delete-ivrs',

            // Voicemails
            'view-all-voicemails',
            'view-own-voicemails',
            'delete-voicemails',
            'forward-voicemails',

            // Block Filters
            'view-block-filters',
            'create-block-filters',
            'edit-block-filters',
            'delete-block-filters',

            // Call Logs
            'view-all-call-logs',
            'view-own-call-logs',
            'view-recordings',
            'download-recordings',
            'add-call-notes',
            'set-dispositions',

            // Analytics
            'view-analytics',
            'export-reports',
            'schedule-reports',

            // Carriers
            'view-carriers',
            'create-carriers',
            'edit-carriers',
            'delete-carriers',

            // Break Codes
            'view-break-codes',
            'create-break-codes',
            'edit-break-codes',
            'delete-break-codes',

            // Hold Music
            'view-hold-music',
            'create-hold-music',
            'edit-hold-music',
            'delete-hold-music',

            // Soundboards
            'view-soundboards',
            'create-soundboards',
            'edit-soundboards',
            'delete-soundboards',

            // Dispositions
            'view-dispositions',
            'create-dispositions',
            'edit-dispositions',
            'delete-dispositions',

            // Users
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // System Settings
            'view-system-settings',
            'edit-system-settings',

            // API Keys
            'view-api-keys',
            'create-api-keys',
            'delete-api-keys',

            // Audit Logs
            'view-audit-logs',
            'export-audit-logs',

            // Backups
            'view-backups',
            'create-backups',
            'restore-backups',
            'delete-backups',

            // Agent Actions
            'make-calls',
            'receive-calls',
            'spy-calls',
            'whisper-calls',
            'barge-calls',
            'transfer-calls',
            'park-calls',
            'use-soundboard',

            // Platform management
            'manage-platform',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superadmin = Role::create(['name' => 'Superadmin']);
        $superadmin->givePermissionTo(Permission::all());

        $admin = Role::create(['name' => 'Admin']);
        $admin->givePermissionTo([
            'view-dashboard', 'view-live-calls', 'view-agent-status', 'view-queue-stats',
            'view-extensions', 'create-extensions', 'edit-extensions', 'delete-extensions',
            'view-extension-groups', 'create-extension-groups', 'edit-extension-groups', 'delete-extension-groups',
            'view-dids', 'create-dids', 'edit-dids', 'delete-dids',
            'view-queues', 'create-queues', 'edit-queues', 'delete-queues', 'manage-queue-agents',
            'view-ring-trees', 'create-ring-trees', 'edit-ring-trees', 'delete-ring-trees',
            'view-ivrs', 'create-ivrs', 'edit-ivrs', 'delete-ivrs',
            'view-all-voicemails', 'delete-voicemails', 'forward-voicemails',
            'view-block-filters', 'create-block-filters', 'edit-block-filters', 'delete-block-filters',
            'view-all-call-logs', 'view-recordings', 'download-recordings', 'add-call-notes', 'set-dispositions',
            'view-analytics', 'export-reports', 'schedule-reports',
            'view-carriers', 'create-carriers', 'edit-carriers', 'delete-carriers',
            'view-break-codes', 'create-break-codes', 'edit-break-codes', 'delete-break-codes',
            'view-hold-music', 'create-hold-music', 'edit-hold-music', 'delete-hold-music',
            'view-soundboards', 'create-soundboards', 'edit-soundboards', 'delete-soundboards',
            'view-dispositions', 'create-dispositions', 'edit-dispositions', 'delete-dispositions',
            'make-calls', 'receive-calls', 'spy-calls', 'whisper-calls', 'barge-calls', 'transfer-calls', 'park-calls', 'use-soundboard',
        ]);

        $qualityAnalyst = Role::create(['name' => 'Quality Analyst']);
        $qualityAnalyst->givePermissionTo([
            'view-dashboard', 'view-live-calls', 'view-agent-status', 'view-queue-stats',
            'view-all-call-logs', 'view-recordings', 'download-recordings', 'add-call-notes',
            'view-analytics', 'export-reports',
            'spy-calls', 'whisper-calls',
        ]);

        $manager = Role::create(['name' => 'Manager']);
        $manager->givePermissionTo([
            'view-dashboard', 'view-live-calls', 'view-agent-status', 'view-queue-stats',
            'view-extensions',
            'view-queues', 'manage-queue-agents',
            'view-all-voicemails', 'forward-voicemails',
            'view-all-call-logs', 'view-recordings', 'download-recordings', 'add-call-notes', 'set-dispositions',
            'view-analytics', 'export-reports',
            'make-calls', 'receive-calls', 'spy-calls', 'whisper-calls', 'barge-calls', 'transfer-calls', 'park-calls', 'use-soundboard',
        ]);

        $agent = Role::create(['name' => 'Agent']);
        $agent->givePermissionTo([
            'view-dashboard',
            'view-own-voicemails', 'forward-voicemails',
            'view-own-call-logs', 'add-call-notes', 'set-dispositions',
            'make-calls', 'receive-calls', 'transfer-calls', 'use-soundboard',
        ]);
    }
}



