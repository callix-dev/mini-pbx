<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\Did;
use App\Models\Carrier;
use App\Models\Extension;
use App\Models\ExtensionGroup;
use App\Models\Queue;
use App\Models\RingTree;
use App\Models\Ivr;
use App\Models\BlockFilterGroup;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DidController extends Controller
{
    public function index(Request $request): View
    {
        $query = Did::with('carrier');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        $dids = $query->orderBy('number')->paginate(25);
        $carriers = Carrier::active()->inbound()->get();

        return view('telephony.dids.index', compact('dids', 'carriers'));
    }

    public function create(): View
    {
        return view('telephony.dids.create', $this->getFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDid($request);

        $did = Did::create($validated);

        AuditLog::log('created', $did, null, $did->toArray(), 'DID created');

        return redirect()->route('dids.index')
            ->with('success', 'DID created successfully.');
    }

    public function show(Did $did): View
    {
        return view('telephony.dids.show', compact('did'));
    }

    public function edit(Did $did): View
    {
        return view('telephony.dids.edit', array_merge(['did' => $did], $this->getFormData()));
    }

    public function update(Request $request, Did $did): RedirectResponse
    {
        $validated = $this->validateDid($request, $did);

        $oldValues = $did->toArray();
        $did->update($validated);

        AuditLog::log('updated', $did, $oldValues, $did->fresh()->toArray(), 'DID updated');

        return redirect()->route('dids.index')
            ->with('success', 'DID updated successfully.');
    }

    public function destroy(Did $did): RedirectResponse
    {
        $oldValues = $did->toArray();
        $did->delete();

        AuditLog::log('deleted', $did, $oldValues, null, 'DID deleted');

        return redirect()->route('dids.index')
            ->with('success', 'DID deleted successfully.');
    }

    public function bulkCreate(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        // TODO: Implement bulk import

        return redirect()->route('dids.index')
            ->with('success', 'DIDs imported successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->bulkCreate($request);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:dids,id',
            'action' => 'required|in:enable,disable,delete',
        ]);

        $dids = Did::whereIn('id', $request->ids)->get();

        foreach ($dids as $did) {
            $oldValues = $did->toArray();

            switch ($request->action) {
                case 'enable':
                    $did->update(['is_active' => true]);
                    break;
                case 'disable':
                    $did->update(['is_active' => false]);
                    break;
                case 'delete':
                    $did->delete();
                    break;
            }

            AuditLog::log($request->action === 'delete' ? 'deleted' : 'updated', $did, $oldValues, 
                $request->action === 'delete' ? null : $did->fresh()->toArray());
        }

        return redirect()->route('dids.index')
            ->with('success', 'Bulk action completed successfully.');
    }

    private function validateDid(Request $request, ?Did $did = null): array
    {
        return $request->validate([
            'number' => 'required|string|max:30|unique:dids,number' . ($did ? ',' . $did->id : ''),
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'carrier_id' => 'nullable|exists:carriers,id',
            'destination_type' => 'nullable|in:' . implode(',', array_keys(Did::DESTINATION_TYPES)),
            'destination_id' => 'nullable|integer',
            'after_hours_destination_type' => 'nullable|in:' . implode(',', array_keys(Did::DESTINATION_TYPES)),
            'after_hours_destination_id' => 'nullable|integer',
            'block_filter_group_id' => 'nullable|exists:block_filter_groups,id',
            'is_active' => 'boolean',
            'time_based_routing' => 'boolean',
            'business_hours' => 'nullable|array',
            'caller_id_routing' => 'nullable|array',
        ]);
    }

    private function getFormData(): array
    {
        return [
            'carriers' => Carrier::active()->inbound()->get(),
            'extensions' => Extension::active()->orderBy('extension')->get(),
            'extensionGroups' => ExtensionGroup::active()->orderBy('name')->get(),
            'queues' => Queue::active()->orderBy('display_name')->get(),
            'ringTrees' => RingTree::active()->orderBy('name')->get(),
            'ivrs' => Ivr::active()->orderBy('name')->get(),
            'blockFilterGroups' => BlockFilterGroup::active()->orderBy('name')->get(),
            'destinationTypes' => Did::DESTINATION_TYPES,
        ];
    }
}







