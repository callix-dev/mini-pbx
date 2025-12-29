<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\ExtensionGroup;
use App\Models\Extension;
use App\Models\Queue;
use App\Models\AuditLog;
use App\Services\Asterisk\ConfigGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ExtensionGroupController extends Controller
{
    public function index(Request $request): View
    {
        $query = ExtensionGroup::withCount('extensions')
            ->with(['extensions' => function ($q) {
                $q->select('extensions.id', 'extension', 'name', 'status');
            }]);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $groups = $query->orderBy('name')->paginate(25);

        // Calculate live stats for each group
        $groups->each(function ($group) {
            $group->live_stats = $group->member_status_counts;
        });

        return view('telephony.extension-groups.index', compact('groups'));
    }

    public function create(): View
    {
        $extensions = Extension::active()->orderBy('extension')->get();
        $ringStrategies = ExtensionGroup::RING_STRATEGIES;
        $destinationTypes = ExtensionGroup::DESTINATION_TYPES;
        
        // Get available destinations for timeout/failover
        $availableExtensions = Extension::active()->orderBy('extension')->get();
        $availableQueues = Queue::active()->orderBy('name')->get();

        // Suggest next available group number
        $lastGroup = ExtensionGroup::whereNotNull('group_number')
            ->orderByRaw('CAST(group_number AS UNSIGNED) DESC')
            ->first();
        $suggestedNumber = $lastGroup ? (int)$lastGroup->group_number + 1 : 1;

        return view('telephony.extension-groups.create', compact(
            'extensions', 
            'ringStrategies', 
            'destinationTypes',
            'availableExtensions',
            'availableQueues',
            'suggestedNumber'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_number' => 'nullable|string|max:10|unique:extension_groups,group_number',
            'pickup_group' => 'nullable|integer|min:1|max:63',
            'description' => 'nullable|string',
            'ring_strategy' => 'required|in:' . implode(',', array_keys(ExtensionGroup::RING_STRATEGIES)),
            'ring_time' => 'required|integer|min:5|max:300',
            'music_on_hold' => 'nullable|string|max:50',
            'announce_holdtime' => 'boolean',
            'announce_position' => 'boolean',
            'record_calls' => 'boolean',
            'timeout_destination_type' => 'nullable|string',
            'timeout_destination_id' => 'nullable|integer',
            'failover_destination_type' => 'nullable|string',
            'failover_destination_id' => 'nullable|integer',
            'extensions' => 'nullable|array',
            'extensions.*' => 'exists:extensions,id',
        ]);

        $group = ExtensionGroup::create([
            'name' => $validated['name'],
            'group_number' => $validated['group_number'] ?: null,
            'pickup_group' => $validated['pickup_group'] ?? null,
            'description' => $validated['description'],
            'ring_strategy' => $validated['ring_strategy'],
            'ring_time' => $validated['ring_time'],
            'music_on_hold' => $validated['music_on_hold'] ?? 'default',
            'announce_holdtime' => $validated['announce_holdtime'] ?? false,
            'announce_position' => $validated['announce_position'] ?? false,
            'record_calls' => $validated['record_calls'] ?? false,
            'timeout_destination_type' => $validated['timeout_destination_type'] ?: null,
            'timeout_destination_id' => $validated['timeout_destination_id'] ?: null,
            'failover_destination_type' => $validated['failover_destination_type'] ?: null,
            'failover_destination_id' => $validated['failover_destination_id'] ?: null,
            'is_active' => true,
        ]);

        if (!empty($validated['extensions'])) {
            $syncData = [];
            foreach ($validated['extensions'] as $priority => $extensionId) {
                $syncData[$extensionId] = ['priority' => $priority];
            }
            $group->extensions()->sync($syncData);
            
            // Sync pickup group to member extensions
            $group->syncPickupGroupToMembers();
        }

        AuditLog::log('created', $group, null, $group->toArray(), 'Extension group created');

        // Regenerate Asterisk configs
        $this->regenerateConfigs();

        return redirect()->route('extension-groups.index')
            ->with('success', "Extension group '{$group->name}' created successfully." . 
                ($group->group_number ? " Dial *6{$group->group_number} to reach this group." : ''));
    }

    public function show(ExtensionGroup $extensionGroup): View
    {
        $extensionGroup->load(['extensions' => function ($q) {
            $q->orderByPivot('priority');
        }]);

        // Get live stats
        $extensionGroup->live_stats = $extensionGroup->member_status_counts;

        return view('telephony.extension-groups.show', compact('extensionGroup'));
    }

    public function edit(ExtensionGroup $extensionGroup): View
    {
        $extensionGroup->load('extensions');
        $extensions = Extension::active()->orderBy('extension')->get();
        $ringStrategies = ExtensionGroup::RING_STRATEGIES;
        $destinationTypes = ExtensionGroup::DESTINATION_TYPES;
        
        $availableExtensions = Extension::active()->orderBy('extension')->get();
        $availableQueues = Queue::active()->orderBy('name')->get();

        return view('telephony.extension-groups.edit', compact(
            'extensionGroup', 
            'extensions', 
            'ringStrategies',
            'destinationTypes',
            'availableExtensions',
            'availableQueues'
        ));
    }

    public function update(Request $request, ExtensionGroup $extensionGroup): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_number' => 'nullable|string|max:10|unique:extension_groups,group_number,' . $extensionGroup->id,
            'pickup_group' => 'nullable|integer|min:1|max:63',
            'description' => 'nullable|string',
            'ring_strategy' => 'required|in:' . implode(',', array_keys(ExtensionGroup::RING_STRATEGIES)),
            'ring_time' => 'required|integer|min:5|max:300',
            'music_on_hold' => 'nullable|string|max:50',
            'announce_holdtime' => 'boolean',
            'announce_position' => 'boolean',
            'record_calls' => 'boolean',
            'timeout_destination_type' => 'nullable|string',
            'timeout_destination_id' => 'nullable|integer',
            'failover_destination_type' => 'nullable|string',
            'failover_destination_id' => 'nullable|integer',
            'extensions' => 'nullable|array',
            'extensions.*' => 'exists:extensions,id',
            'is_active' => 'boolean',
        ]);

        $oldValues = $extensionGroup->toArray();

        $extensionGroup->update([
            'name' => $validated['name'],
            'group_number' => $validated['group_number'] ?: null,
            'pickup_group' => $validated['pickup_group'] ?? null,
            'description' => $validated['description'],
            'ring_strategy' => $validated['ring_strategy'],
            'ring_time' => $validated['ring_time'],
            'music_on_hold' => $validated['music_on_hold'] ?? 'default',
            'announce_holdtime' => $validated['announce_holdtime'] ?? false,
            'announce_position' => $validated['announce_position'] ?? false,
            'record_calls' => $validated['record_calls'] ?? false,
            'timeout_destination_type' => $validated['timeout_destination_type'] ?: null,
            'timeout_destination_id' => $validated['timeout_destination_id'] ?: null,
            'failover_destination_type' => $validated['failover_destination_type'] ?: null,
            'failover_destination_id' => $validated['failover_destination_id'] ?: null,
            'is_active' => $validated['is_active'] ?? $extensionGroup->is_active,
        ]);

        $syncData = [];
        if (!empty($validated['extensions'])) {
            foreach ($validated['extensions'] as $priority => $extensionId) {
                $syncData[$extensionId] = ['priority' => $priority];
            }
        }
        $extensionGroup->extensions()->sync($syncData);
        
        // Sync pickup group to member extensions
        $extensionGroup->syncPickupGroupToMembers();

        AuditLog::log('updated', $extensionGroup, $oldValues, $extensionGroup->fresh()->toArray(), 'Extension group updated');

        // Regenerate Asterisk configs
        $this->regenerateConfigs();

        return redirect()->route('extension-groups.index')
            ->with('success', 'Extension group updated successfully.');
    }

    public function destroy(ExtensionGroup $extensionGroup): RedirectResponse
    {
        $oldValues = $extensionGroup->toArray();
        $name = $extensionGroup->name;
        $extensionGroup->delete();

        AuditLog::log('deleted', $extensionGroup, $oldValues, null, 'Extension group deleted');

        // Regenerate Asterisk configs
        $this->regenerateConfigs();

        return redirect()->route('extension-groups.index')
            ->with('success', "Extension group '{$name}' deleted successfully.");
    }

    /**
     * Get live status of group members (API endpoint)
     */
    public function liveStatus(ExtensionGroup $extensionGroup): JsonResponse
    {
        $extensionGroup->load(['extensions' => function ($q) {
            $q->select('extensions.id', 'extension', 'name', 'status', 'last_registered_at');
        }]);

        $members = $extensionGroup->extensions->map(function ($ext) {
            return [
                'id' => $ext->id,
                'extension' => $ext->extension,
                'name' => $ext->name,
                'status' => $ext->status,
                'last_registered_at' => $ext->last_registered_at?->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'group' => [
                'id' => $extensionGroup->id,
                'name' => $extensionGroup->name,
                'stats' => $extensionGroup->member_status_counts,
                'statistics' => [
                    'total_calls' => $extensionGroup->total_calls,
                    'answered_calls' => $extensionGroup->answered_calls,
                    'missed_calls' => $extensionGroup->missed_calls,
                    'answer_rate' => $extensionGroup->answer_rate . '%',
                    'avg_talk_time' => $extensionGroup->formatted_avg_talk_time,
                ],
            ],
            'members' => $members,
        ]);
    }

    /**
     * Reset group statistics
     */
    public function resetStats(ExtensionGroup $extensionGroup): RedirectResponse
    {
        $extensionGroup->resetStatistics();
        
        AuditLog::log('updated', $extensionGroup, 
            ['total_calls' => $extensionGroup->total_calls], 
            ['total_calls' => 0], 
            'Group statistics reset');

        return redirect()->back()->with('success', 'Group statistics have been reset.');
    }

    /**
     * Regenerate Asterisk configuration files
     */
    protected function regenerateConfigs(): void
    {
        try {
            $generator = new ConfigGenerator();
            $generator->regenerateAllConfigs();
            $generator->reloadDialplan();
        } catch (\Exception $e) {
            \Log::error('Failed to regenerate Asterisk configs: ' . $e->getMessage());
        }
    }
}
