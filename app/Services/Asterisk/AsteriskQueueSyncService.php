<?php

namespace App\Services\Asterisk;

use App\Models\ExtensionGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AsteriskQueueSyncService
{
    /**
     * Queue name prefix for extension groups
     */
    protected const QUEUE_PREFIX = 'extgroup_';

    /**
     * Map Laravel ring strategies to Asterisk queue strategies
     */
    protected const STRATEGY_MAP = [
        'ringall' => 'ringall',
        'hunt' => 'linear',
        'memoryhunt' => 'rrmemory',
        'leastrecent' => 'leastrecent',
        'fewestcalls' => 'fewestcalls',
        'random' => 'random',
    ];

    /**
     * Sync an extension group to Asterisk queue tables
     */
    public function syncExtensionGroup(ExtensionGroup $group): void
    {
        $queueName = $this->getQueueName($group);
        
        try {
            DB::beginTransaction();

            // Map ring strategy
            $strategy = self::STRATEGY_MAP[$group->ring_strategy] ?? 'ringall';

            // Check if queue exists
            $exists = DB::table('asterisk_queues')->where('name', $queueName)->exists();

            if ($exists) {
                // Update existing queue
                DB::table('asterisk_queues')
                    ->where('name', $queueName)
                    ->update([
                        'strategy' => $strategy,
                        'timeout' => $group->ring_time ?? 30,
                        'musicclass' => $group->settings['musicclass'] ?? 'default',
                        'announce' => $group->settings['announce'] ?? null,
                        'wrapuptime' => $group->settings['wrapuptime'] ?? 0,
                        'maxlen' => $group->settings['maxlen'] ?? 0,
                        'joinempty' => $group->settings['joinempty'] ?? 'yes',
                        'leavewhenempty' => $group->settings['leavewhenempty'] ?? 'no',
                        'ringinuse' => $group->settings['ringinuse'] ?? 'yes',
                        'extension_group_id' => $group->id,
                        'updated_at' => now(),
                    ]);
            } else {
                // Insert new queue
                DB::table('asterisk_queues')->insert([
                    'name' => $queueName,
                    'strategy' => $strategy,
                    'timeout' => $group->ring_time ?? 30,
                    'musicclass' => $group->settings['musicclass'] ?? 'default',
                    'announce' => $group->settings['announce'] ?? null,
                    'wrapuptime' => $group->settings['wrapuptime'] ?? 0,
                    'maxlen' => $group->settings['maxlen'] ?? 0,
                    'joinempty' => $group->settings['joinempty'] ?? 'yes',
                    'leavewhenempty' => $group->settings['leavewhenempty'] ?? 'no',
                    'ringinuse' => $group->settings['ringinuse'] ?? 'yes',
                    'extension_group_id' => $group->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Sync queue members
            $this->syncQueueMembers($group, $queueName);

            DB::commit();

            Log::info('Extension group synced to Asterisk queue', [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'queue_name' => $queueName,
                'strategy' => $strategy,
                'members_count' => $group->extensions->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to sync extension group to Asterisk queue', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync queue members from extension group
     */
    protected function syncQueueMembers(ExtensionGroup $group, string $queueName): void
    {
        // Remove existing members
        DB::table('asterisk_queue_members')
            ->where('queue_name', $queueName)
            ->delete();

        // Load extensions if not already loaded
        if (!$group->relationLoaded('extensions')) {
            $group->load('extensions');
        }

        // Add current members
        $members = [];
        foreach ($group->extensions as $extension) {
            if (!$extension->is_active) {
                continue;
            }

            $members[] = [
                'queue_name' => $queueName,
                'interface' => 'PJSIP/' . $extension->extension,
                'membername' => $extension->name,
                'state_interface' => 'PJSIP/' . $extension->extension,
                'penalty' => $extension->pivot->priority ?? 0,
                'paused' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($members)) {
            DB::table('asterisk_queue_members')->insert($members);
        }
    }

    /**
     * Delete queue when extension group is deleted
     */
    public function deleteExtensionGroupQueue(ExtensionGroup $group): void
    {
        $queueName = $this->getQueueName($group);

        try {
            DB::beginTransaction();

            // Remove members first
            DB::table('asterisk_queue_members')
                ->where('queue_name', $queueName)
                ->delete();

            // Remove queue
            DB::table('asterisk_queues')
                ->where('name', $queueName)
                ->delete();

            DB::commit();

            Log::info('Extension group queue deleted', [
                'group_id' => $group->id,
                'queue_name' => $queueName,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete extension group queue', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync all extension groups to Asterisk queues
     */
    public function syncAllExtensionGroups(): void
    {
        $groups = ExtensionGroup::with('extensions')->active()->get();

        foreach ($groups as $group) {
            $this->syncExtensionGroup($group);
        }

        // Clean up orphaned queues (groups that no longer exist)
        $this->cleanupOrphanedQueues();
    }

    /**
     * Remove queues for extension groups that no longer exist
     */
    protected function cleanupOrphanedQueues(): void
    {
        $activeGroupIds = ExtensionGroup::active()->pluck('id')->toArray();

        // Get all extgroup_ queues
        $orphanedQueues = DB::table('asterisk_queues')
            ->where('name', 'like', self::QUEUE_PREFIX . '%')
            ->whereNotIn('extension_group_id', $activeGroupIds)
            ->pluck('name');

        foreach ($orphanedQueues as $queueName) {
            DB::table('asterisk_queue_members')
                ->where('queue_name', $queueName)
                ->delete();

            DB::table('asterisk_queues')
                ->where('name', $queueName)
                ->delete();

            Log::info('Orphaned queue cleaned up', ['queue_name' => $queueName]);
        }
    }

    /**
     * Get the Asterisk queue name for an extension group
     */
    public function getQueueName(ExtensionGroup $group): string
    {
        return self::QUEUE_PREFIX . $group->id;
    }

    /**
     * Get extension group ID from queue name
     */
    public static function getGroupIdFromQueueName(string $queueName): ?int
    {
        if (str_starts_with($queueName, self::QUEUE_PREFIX)) {
            return (int) substr($queueName, strlen(self::QUEUE_PREFIX));
        }
        return null;
    }

    /**
     * Check if a queue name is an extension group queue
     */
    public static function isExtensionGroupQueue(string $queueName): bool
    {
        return str_starts_with($queueName, self::QUEUE_PREFIX);
    }

    /**
     * Pause/unpause a member in the queue
     */
    public function setMemberPaused(string $queueName, string $extension, bool $paused): void
    {
        DB::table('asterisk_queue_members')
            ->where('queue_name', $queueName)
            ->where('interface', 'PJSIP/' . $extension)
            ->update([
                'paused' => $paused ? 1 : 0,
                'updated_at' => now(),
            ]);
    }

    /**
     * Add a single member to queue
     */
    public function addMember(string $queueName, string $extension, string $name = null, int $penalty = 0): void
    {
        DB::table('asterisk_queue_members')->updateOrInsert(
            [
                'queue_name' => $queueName,
                'interface' => 'PJSIP/' . $extension,
            ],
            [
                'membername' => $name,
                'state_interface' => 'PJSIP/' . $extension,
                'penalty' => $penalty,
                'paused' => 0,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
    }

    /**
     * Remove a single member from queue
     */
    public function removeMember(string $queueName, string $extension): void
    {
        DB::table('asterisk_queue_members')
            ->where('queue_name', $queueName)
            ->where('interface', 'PJSIP/' . $extension)
            ->delete();
    }
}

