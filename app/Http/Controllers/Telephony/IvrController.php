<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\Ivr;
use App\Models\IvrNode;
use App\Models\AudioFile;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IvrController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ivr::withCount('nodes');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $ivrs = $query->orderBy('name')->paginate(25);

        return view('telephony.ivrs.index', compact('ivrs'));
    }

    public function create(): View
    {
        $audioFiles = AudioFile::active()->ofType('ivr_prompt')->get();
        $nodeTypes = IvrNode::TYPES;

        return view('telephony.ivrs.create', compact('audioFiles', 'nodeTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timeout' => 'required|integer|min:1|max:30',
            'invalid_retries' => 'required|integer|min:1|max:5',
            'direct_dial' => 'boolean',
        ]);

        $validated['is_active'] = true;

        $ivr = Ivr::create($validated);

        AuditLog::log('created', $ivr, null, $ivr->toArray(), 'IVR created');

        return redirect()->route('ivrs.edit', $ivr)
            ->with('success', 'IVR created. Now configure the flow.');
    }

    public function show(Ivr $ivr): View
    {
        $ivr->load(['nodes.audioFile', 'nodes.outgoingConnections.toNode']);

        return view('telephony.ivrs.show', compact('ivr'));
    }

    public function edit(Ivr $ivr): View
    {
        $ivr->load(['nodes.audioFile', 'nodes.outgoingConnections']);
        $audioFiles = AudioFile::active()->ofType('ivr_prompt')->get();
        $nodeTypes = IvrNode::TYPES;

        return view('telephony.ivrs.edit', compact('ivr', 'audioFiles', 'nodeTypes'));
    }

    public function update(Request $request, Ivr $ivr): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timeout' => 'required|integer|min:1|max:30',
            'invalid_retries' => 'required|integer|min:1|max:5',
            'direct_dial' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $oldValues = $ivr->toArray();
        $ivr->update($validated);

        AuditLog::log('updated', $ivr, $oldValues, $ivr->fresh()->toArray(), 'IVR updated');

        return redirect()->route('ivrs.index')
            ->with('success', 'IVR updated successfully.');
    }

    public function destroy(Ivr $ivr): RedirectResponse
    {
        $oldValues = $ivr->toArray();
        $ivr->delete();

        AuditLog::log('deleted', $ivr, $oldValues, null, 'IVR deleted');

        return redirect()->route('ivrs.index')
            ->with('success', 'IVR deleted successfully.');
    }

    public function saveNodes(Request $request, Ivr $ivr): RedirectResponse
    {
        $request->validate([
            'nodes' => 'array',
            'nodes.*.type' => 'required|in:' . implode(',', array_keys(IvrNode::TYPES)),
            'nodes.*.digit' => 'nullable|string|max:2',
            'nodes.*.audio_file_id' => 'nullable|exists:audio_files,id',
            'nodes.*.destination_type' => 'nullable|string',
            'nodes.*.destination_id' => 'nullable|integer',
            'nodes.*.position_x' => 'required|integer',
            'nodes.*.position_y' => 'required|integer',
            'connections' => 'array',
        ]);

        // Delete existing nodes
        $ivr->nodes()->delete();

        // Create new nodes
        $nodeIdMap = [];
        foreach ($request->input('nodes', []) as $tempId => $nodeData) {
            $node = $ivr->nodes()->create([
                'type' => $nodeData['type'],
                'digit' => $nodeData['digit'] ?? null,
                'audio_file_id' => $nodeData['audio_file_id'] ?? null,
                'destination_type' => $nodeData['destination_type'] ?? null,
                'destination_id' => $nodeData['destination_id'] ?? null,
                'position_x' => $nodeData['position_x'],
                'position_y' => $nodeData['position_y'],
                'time_conditions' => $nodeData['time_conditions'] ?? null,
            ]);
            $nodeIdMap[$tempId] = $node->id;
        }

        // Create connections
        foreach ($request->input('connections', []) as $connection) {
            if (isset($nodeIdMap[$connection['from']]) && isset($nodeIdMap[$connection['to']])) {
                \App\Models\IvrNodeConnection::create([
                    'from_node_id' => $nodeIdMap[$connection['from']],
                    'to_node_id' => $nodeIdMap[$connection['to']],
                    'condition' => $connection['condition'] ?? null,
                ]);
            }
        }

        return redirect()->back()
            ->with('success', 'IVR flow saved successfully.');
    }
}





