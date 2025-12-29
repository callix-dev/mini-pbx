<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\BlockFilterGroup;
use App\Models\BlockFilter;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlockFilterController extends Controller
{
    public function index(Request $request): View
    {
        $query = BlockFilterGroup::withCount(['filters', 'blacklist', 'whitelist']);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $groups = $query->orderBy('name')->paginate(25);

        return view('telephony.block-filters.index', compact('groups'));
    }

    public function create(): View
    {
        return view('telephony.block-filters.create', [
            'types' => BlockFilter::TYPES,
            'matchTypes' => BlockFilter::MATCH_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'filters' => 'nullable|array',
            'filters.*.type' => 'required|in:blacklist,whitelist',
            'filters.*.pattern' => 'required|string|max:255',
            'filters.*.match_type' => 'required|in:exact,prefix,regex',
            'filters.*.name' => 'nullable|string|max:255',
            'filters.*.expires_at' => 'nullable|date',
        ]);

        $group = BlockFilterGroup::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        foreach ($validated['filters'] ?? [] as $filterData) {
            $group->filters()->create(array_merge($filterData, ['is_active' => true]));
        }

        AuditLog::log('created', $group, null, $group->toArray(), 'Block filter group created');

        return redirect()->route('block-filters.index')
            ->with('success', 'Block filter group created successfully.');
    }

    public function show(BlockFilterGroup $blockFilter): View
    {
        $blockFilter->load('filters');

        return view('telephony.block-filters.show', ['group' => $blockFilter]);
    }

    public function edit(BlockFilterGroup $blockFilter): View
    {
        $blockFilter->load('filters');

        return view('telephony.block-filters.edit', [
            'group' => $blockFilter,
            'types' => BlockFilter::TYPES,
            'matchTypes' => BlockFilter::MATCH_TYPES,
        ]);
    }

    public function update(Request $request, BlockFilterGroup $blockFilter): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'filters' => 'nullable|array',
            'filters.*.id' => 'nullable|exists:block_filters,id',
            'filters.*.type' => 'required|in:blacklist,whitelist',
            'filters.*.pattern' => 'required|string|max:255',
            'filters.*.match_type' => 'required|in:exact,prefix,regex',
            'filters.*.name' => 'nullable|string|max:255',
            'filters.*.expires_at' => 'nullable|date',
            'filters.*.is_active' => 'boolean',
        ]);

        $oldValues = $blockFilter->toArray();

        $blockFilter->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Sync filters
        $existingIds = [];
        foreach ($validated['filters'] ?? [] as $filterData) {
            if (!empty($filterData['id'])) {
                $filter = $blockFilter->filters()->find($filterData['id']);
                if ($filter) {
                    $filter->update($filterData);
                    $existingIds[] = $filter->id;
                }
            } else {
                $filter = $blockFilter->filters()->create(array_merge($filterData, ['is_active' => $filterData['is_active'] ?? true]));
                $existingIds[] = $filter->id;
            }
        }

        // Delete removed filters
        $blockFilter->filters()->whereNotIn('id', $existingIds)->delete();

        AuditLog::log('updated', $blockFilter, $oldValues, $blockFilter->fresh()->toArray(), 'Block filter group updated');

        return redirect()->route('block-filters.index')
            ->with('success', 'Block filter group updated successfully.');
    }

    public function destroy(BlockFilterGroup $blockFilter): RedirectResponse
    {
        $oldValues = $blockFilter->toArray();
        $blockFilter->delete();

        AuditLog::log('deleted', $blockFilter, $oldValues, null, 'Block filter group deleted');

        return redirect()->route('block-filters.index')
            ->with('success', 'Block filter group deleted successfully.');
    }
}







