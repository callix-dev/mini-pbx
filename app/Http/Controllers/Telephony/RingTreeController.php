<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\RingTree;
use App\Models\RingTreeNode;
use App\Models\Extension;
use App\Models\ExtensionGroup;
use App\Models\Queue;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RingTreeController extends Controller
{
    public function index(Request $request): View
    {
        $query = RingTree::withCount('nodes');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $ringTrees = $query->orderBy('name')->paginate(25);

        return view('telephony.ring-trees.index', compact('ringTrees'));
    }

    public function create(): View
    {
        return view('telephony.ring-trees.create', $this->getFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'nullable|array',
        ]);

        $ringTree = RingTree::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        if (!empty($validated['nodes'])) {
            $this->saveNodes($ringTree, $validated['nodes']);
        }

        AuditLog::log('created', $ringTree, null, $ringTree->toArray(), 'Ring tree created');

        return redirect()->route('ring-trees.index')
            ->with('success', 'Ring tree created successfully.');
    }

    public function show(RingTree $ringTree): View
    {
        $ringTree->load('nodes');

        return view('telephony.ring-trees.show', compact('ringTree'));
    }

    public function edit(RingTree $ringTree): View
    {
        $ringTree->load('nodes');

        return view('telephony.ring-trees.edit', array_merge(['ringTree' => $ringTree], $this->getFormData()));
    }

    public function update(Request $request, RingTree $ringTree): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'nullable|array',
        ]);

        $oldValues = $ringTree->toArray();

        $ringTree->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        // Delete existing nodes and recreate
        $ringTree->nodes()->delete();
        if (!empty($validated['nodes'])) {
            $this->saveNodes($ringTree, $validated['nodes']);
        }

        AuditLog::log('updated', $ringTree, $oldValues, $ringTree->fresh()->toArray(), 'Ring tree updated');

        return redirect()->route('ring-trees.index')
            ->with('success', 'Ring tree updated successfully.');
    }

    public function destroy(RingTree $ringTree): RedirectResponse
    {
        $oldValues = $ringTree->toArray();
        $ringTree->delete();

        AuditLog::log('deleted', $ringTree, $oldValues, null, 'Ring tree deleted');

        return redirect()->route('ring-trees.index')
            ->with('success', 'Ring tree deleted successfully.');
    }

    private function saveNodes(RingTree $ringTree, array $nodes, ?int $parentId = null, int $level = 1): void
    {
        foreach ($nodes as $position => $nodeData) {
            $node = $ringTree->nodes()->create([
                'parent_id' => $parentId,
                'level' => $level,
                'position' => $position,
                'destination_type' => $nodeData['destination_type'],
                'destination_id' => $nodeData['destination_id'] ?? null,
                'timeout' => $nodeData['timeout'] ?? 20,
            ]);

            if (!empty($nodeData['children']) && $level < 3) {
                $this->saveNodes($ringTree, $nodeData['children'], $node->id, $level + 1);
            }
        }
    }

    private function getFormData(): array
    {
        return [
            'extensions' => Extension::active()->orderBy('extension')->get(),
            'extensionGroups' => ExtensionGroup::active()->orderBy('name')->get(),
            'queues' => Queue::active()->orderBy('display_name')->get(),
            'destinationTypes' => RingTreeNode::DESTINATION_TYPES,
        ];
    }
}

