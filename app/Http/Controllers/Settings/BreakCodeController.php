<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\BreakCode;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BreakCodeController extends Controller
{
    public function index(): View
    {
        $breakCodes = BreakCode::withCount('agentBreaks')
            ->ordered()
            ->paginate(25);

        return view('settings.break-codes.index', compact('breakCodes'));
    }

    public function create(): View
    {
        return view('settings.break-codes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:break_codes',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'is_paid' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = true;

        $breakCode = BreakCode::create($validated);

        AuditLog::log('created', $breakCode, null, $breakCode->toArray(), 'Break code created');

        return redirect()->route('break-codes.index')
            ->with('success', 'Break code created successfully.');
    }

    public function edit(BreakCode $breakCode): View
    {
        return view('settings.break-codes.edit', compact('breakCode'));
    }

    public function update(Request $request, BreakCode $breakCode): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:break_codes,code,' . $breakCode->id,
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $oldValues = $breakCode->toArray();
        $breakCode->update($validated);

        AuditLog::log('updated', $breakCode, $oldValues, $breakCode->fresh()->toArray(), 'Break code updated');

        return redirect()->route('break-codes.index')
            ->with('success', 'Break code updated successfully.');
    }

    public function destroy(BreakCode $breakCode): RedirectResponse
    {
        $oldValues = $breakCode->toArray();
        $breakCode->delete();

        AuditLog::log('deleted', $breakCode, $oldValues, null, 'Break code deleted');

        return redirect()->route('break-codes.index')
            ->with('success', 'Break code deleted successfully.');
    }
}







