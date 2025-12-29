<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\ExtensionGroup;
use App\Models\Extension;
use App\Models\AuditLog;
use App\Services\Asterisk\AsteriskQueueSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExtensionGroupController extends Controller
{
    protected AsteriskQueueSyncService $queueSyncService;

    public function __construct(AsteriskQueueSyncService $queueSyncService)
    {
        $this->queueSyncService = $queueSyncService;
    }

    public function index(Request $request): View
    {
        $query = ExtensionGroup::withCount('extensions');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $groups = $query->orderBy('name')->paginate(25);

        return view('telephony.extension-groups.index', compact('groups'));
    }

    public function create(): View
    {
        $extensions = Extension::active()->orderBy('extension')->get();
        $ringStrategies = ExtensionGroup::RING_STRATEGIES;

        return view('telephony.extension-groups.create', compact('extensions', 'ringStrategies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ring_strategy' => 'required|in:' . implode(',', array_keys(ExtensionGroup::RING_STRATEGIES)),
            'ring_time' => 'required|integer|min:5|max:300',
            'extensions' => 'nullable|array',
            'extensions.*' => 'exists:extensions,id',
        ]);

        $group = ExtensionGroup::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'ring_strategy' => $validated['ring_strategy'],
            'ring_time' => $validated['ring_time'],
            'is_active' => true,
        ]);

        if (!empty($validated['extensions'])) {
            $syncData = [];
            foreach ($validated['extensions'] as $priority => $extensionId) {
                $syncData[$extensionId] = ['priority' => $priority];
            }
            $group->extensions()->sync($syncData);
        }

        // Sync to Asterisk queue (members were added after create, observer won't catch pivot changes)
        $group->load('extensions');
        $this->queueSyncService->syncExtensionGroup($group);

        AuditLog::log('created', $group, null, $group->toArray(), 'Extension group created');

        return redirect()->route('extension-groups.index')
            ->with('success', 'Extension group created successfully.');
    }

    public function show(ExtensionGroup $extensionGroup): View
    {
        $extensionGroup->load('extensions');

        return view('telephony.extension-groups.show', compact('extensionGroup'));
    }

    public function edit(ExtensionGroup $extensionGroup): View
    {
        $extensionGroup->load('extensions');
        $extensions = Extension::active()->orderBy('extension')->get();
        $ringStrategies = ExtensionGroup::RING_STRATEGIES;

        return view('telephony.extension-groups.edit', compact('extensionGroup', 'extensions', 'ringStrategies'));
    }

    public function update(Request $request, ExtensionGroup $extensionGroup): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ring_strategy' => 'required|in:' . implode(',', array_keys(ExtensionGroup::RING_STRATEGIES)),
            'ring_time' => 'required|integer|min:5|max:300',
            'extensions' => 'nullable|array',
            'extensions.*' => 'exists:extensions,id',
        ]);

        $oldValues = $extensionGroup->toArray();

        $extensionGroup->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'ring_strategy' => $validated['ring_strategy'],
            'ring_time' => $validated['ring_time'],
        ]);

        $syncData = [];
        if (!empty($validated['extensions'])) {
            foreach ($validated['extensions'] as $priority => $extensionId) {
                $syncData[$extensionId] = ['priority' => $priority];
            }
        }
        $extensionGroup->extensions()->sync($syncData);

        // Sync to Asterisk queue (pivot changes don't trigger model observer)
        $extensionGroup->load('extensions');
        $this->queueSyncService->syncExtensionGroup($extensionGroup);

        AuditLog::log('updated', $extensionGroup, $oldValues, $extensionGroup->fresh()->toArray(), 'Extension group updated');

        return redirect()->route('extension-groups.index')
            ->with('success', 'Extension group updated successfully.');
    }

    public function destroy(ExtensionGroup $extensionGroup): RedirectResponse
    {
        $oldValues = $extensionGroup->toArray();
        $extensionGroup->delete();

        AuditLog::log('deleted', $extensionGroup, $oldValues, null, 'Extension group deleted');

        return redirect()->route('extension-groups.index')
            ->with('success', 'Extension group deleted successfully.');
    }
}





