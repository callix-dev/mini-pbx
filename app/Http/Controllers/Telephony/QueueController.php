<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\Extension;
use App\Models\HoldMusic;
use App\Models\Soundboard;
use App\Models\BlockFilterGroup;
use App\Models\VipCaller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $query = Queue::withCount(['members', 'members as logged_in_count' => function ($q) {
            $q->where('is_logged_in', true);
        }]);

        if ($request->filled('search')) {
            $query->where('display_name', 'like', "%{$request->search}%");
        }

        $queues = $query->orderBy('display_name')->paginate(25);

        return view('telephony.queues.index', compact('queues'));
    }

    public function create(): View
    {
        return view('telephony.queues.create', $this->getFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateQueue($request);

        $queue = Queue::create($validated);

        if ($request->has('agent_ids')) {
            $this->syncAgents($queue, $request->input('agent_ids', []), $request->input('penalties', []));
        }

        AuditLog::log('created', $queue, null, $queue->toArray(), 'Queue created');

        return redirect()->route('queues.index')
            ->with('success', 'Queue created successfully.');
    }

    public function show(Queue $queue): View
    {
        $queue->load(['members.extension', 'vipCallers', 'holdMusic', 'soundboard']);

        return view('telephony.queues.show', compact('queue'));
    }

    public function edit(Queue $queue): View
    {
        $queue->load(['members.extension', 'vipCallers']);

        return view('telephony.queues.edit', array_merge(['queue' => $queue], $this->getFormData()));
    }

    public function update(Request $request, Queue $queue): RedirectResponse
    {
        $validated = $this->validateQueue($request, $queue);

        $oldValues = $queue->toArray();
        $queue->update($validated);

        if ($request->has('agent_ids')) {
            $this->syncAgents($queue, $request->input('agent_ids', []), $request->input('penalties', []));
        }

        AuditLog::log('updated', $queue, $oldValues, $queue->fresh()->toArray(), 'Queue updated');

        return redirect()->route('queues.index')
            ->with('success', 'Queue updated successfully.');
    }

    public function destroy(Queue $queue): RedirectResponse
    {
        $oldValues = $queue->toArray();
        $queue->delete();

        AuditLog::log('deleted', $queue, $oldValues, null, 'Queue deleted');

        return redirect()->route('queues.index')
            ->with('success', 'Queue deleted successfully.');
    }

    public function updateAgents(Request $request, Queue $queue): RedirectResponse
    {
        $request->validate([
            'agent_ids' => 'array',
            'agent_ids.*' => 'exists:extensions,id',
            'penalties' => 'array',
            'auto_login' => 'array',
        ]);

        $this->syncAgents(
            $queue,
            $request->input('agent_ids', []),
            $request->input('penalties', []),
            $request->input('auto_login', [])
        );

        return redirect()->back()
            ->with('success', 'Queue agents updated successfully.');
    }

    public function updateVipCallers(Request $request, Queue $queue): RedirectResponse
    {
        $request->validate([
            'vip_callers' => 'array',
            'vip_callers.*.caller_id' => 'required|string|max:30',
            'vip_callers.*.name' => 'nullable|string|max:255',
            'vip_callers.*.priority' => 'required|integer|min:1|max:10',
        ]);

        $queue->vipCallers()->delete();

        foreach ($request->input('vip_callers', []) as $vipCaller) {
            $queue->vipCallers()->create($vipCaller);
        }

        return redirect()->back()
            ->with('success', 'VIP callers updated successfully.');
    }

    private function validateQueue(Request $request, ?Queue $queue = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255|unique:queues,name' . ($queue ? ',' . $queue->id : ''),
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'strategy' => 'required|in:' . implode(',', array_keys(Queue::STRATEGIES)),
            'timeout' => 'required|integer|min:5|max:300',
            'retry' => 'required|integer|min:0|max:60',
            'wrapuptime' => 'required|integer|min:0|max:300',
            'maxlen' => 'required|integer|min:0',
            'weight' => 'required|integer|min:0|max:100',
            'joinempty' => 'boolean',
            'leavewhenempty' => 'boolean',
            'hold_music_id' => 'nullable|exists:hold_music,id',
            'soundboard_id' => 'nullable|exists:soundboards,id',
            'block_filter_group_id' => 'nullable|exists:block_filter_groups,id',
            'announce_holdtime' => 'required|in:yes,no,once',
            'announce_position' => 'required|in:yes,no,limit,more',
            'is_active' => 'boolean',
            'record_calls' => 'boolean',
            'priority_queue' => 'boolean',
            'business_hours' => 'nullable|array',
        ]);
    }

    private function syncAgents(Queue $queue, array $agentIds, array $penalties = [], array $autoLogin = []): void
    {
        $syncData = [];
        foreach ($agentIds as $index => $extensionId) {
            $syncData[$extensionId] = [
                'penalty' => $penalties[$index] ?? 0,
                'auto_login' => in_array($extensionId, $autoLogin),
            ];
        }
        $queue->extensions()->sync($syncData);
    }

    private function getFormData(): array
    {
        return [
            'extensions' => Extension::active()->orderBy('extension')->get(),
            'holdMusic' => HoldMusic::active()->orderBy('name')->get(),
            'soundboards' => Soundboard::active()->orderBy('name')->get(),
            'blockFilterGroups' => BlockFilterGroup::active()->orderBy('name')->get(),
            'strategies' => Queue::STRATEGIES,
        ];
    }
}





