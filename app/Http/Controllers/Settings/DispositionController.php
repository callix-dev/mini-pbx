<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DispositionController extends Controller
{
    public function index(): View
    {
        $dispositions = Disposition::withCount('callLogs')
            ->ordered()
            ->paginate(25);

        return view('settings.dispositions.index', compact('dispositions'));
    }

    public function create(): View
    {
        return view('settings.dispositions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:dispositions',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'requires_callback' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = true;

        $disposition = Disposition::create($validated);

        if ($validated['is_default'] ?? false) {
            $disposition->setAsDefault();
        }

        AuditLog::log('created', $disposition, null, $disposition->toArray(), 'Disposition created');

        return redirect()->route('dispositions.index')
            ->with('success', 'Disposition created successfully.');
    }

    public function edit(Disposition $disposition): View
    {
        return view('settings.dispositions.edit', compact('disposition'));
    }

    public function update(Request $request, Disposition $disposition): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:dispositions,code,' . $disposition->id,
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'requires_callback' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $oldValues = $disposition->toArray();
        $disposition->update($validated);

        if ($validated['is_default'] ?? false) {
            $disposition->setAsDefault();
        }

        AuditLog::log('updated', $disposition, $oldValues, $disposition->fresh()->toArray(), 'Disposition updated');

        return redirect()->route('dispositions.index')
            ->with('success', 'Disposition updated successfully.');
    }

    public function destroy(Disposition $disposition): RedirectResponse
    {
        $oldValues = $disposition->toArray();
        $disposition->delete();

        AuditLog::log('deleted', $disposition, $oldValues, null, 'Disposition deleted');

        return redirect()->route('dispositions.index')
            ->with('success', 'Disposition deleted successfully.');
    }
}

