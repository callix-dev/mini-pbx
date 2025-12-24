<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Soundboard;
use App\Models\SoundboardClip;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SoundboardController extends Controller
{
    public function index(): View
    {
        $soundboards = Soundboard::withCount('clips')
            ->orderBy('name')
            ->paginate(25);

        return view('settings.soundboards.index', compact('soundboards'));
    }

    public function create(): View
    {
        return view('settings.soundboards.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = true;

        $soundboard = Soundboard::create($validated);

        AuditLog::log('created', $soundboard, null, $soundboard->toArray(), 'Soundboard created');

        return redirect()->route('soundboards.edit', $soundboard)
            ->with('success', 'Soundboard created. Now upload audio clips.');
    }

    public function show(Soundboard $soundboard): View
    {
        $soundboard->load('clips');

        return view('settings.soundboards.show', compact('soundboard'));
    }

    public function edit(Soundboard $soundboard): View
    {
        $soundboard->load('clips');

        return view('settings.soundboards.edit', compact('soundboard'));
    }

    public function update(Request $request, Soundboard $soundboard): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldValues = $soundboard->toArray();
        $soundboard->update($validated);

        AuditLog::log('updated', $soundboard, $oldValues, $soundboard->fresh()->toArray(), 'Soundboard updated');

        return redirect()->route('soundboards.index')
            ->with('success', 'Soundboard updated successfully.');
    }

    public function destroy(Soundboard $soundboard): RedirectResponse
    {
        $oldValues = $soundboard->toArray();

        // Delete files
        foreach ($soundboard->clips as $clip) {
            if (file_exists(storage_path('app/' . $clip->file_path))) {
                unlink(storage_path('app/' . $clip->file_path));
            }
        }

        $soundboard->delete();

        AuditLog::log('deleted', $soundboard, $oldValues, null, 'Soundboard deleted');

        return redirect()->route('soundboards.index')
            ->with('success', 'Soundboard deleted successfully.');
    }

    public function uploadClip(Request $request, Soundboard $soundboard): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:mp3,wav,ogg|max:10240', // 10MB max
            'shortcut_key' => 'nullable|string|max:10',
        ]);

        $file = $request->file('file');
        $path = $file->store('soundboards/' . $soundboard->id);

        $soundboard->clips()->create([
            'name' => $request->name,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'shortcut_key' => $request->shortcut_key,
            'sort_order' => $soundboard->clips()->count(),
            'is_active' => true,
        ]);

        return redirect()->back()
            ->with('success', 'Audio clip uploaded successfully.');
    }

    public function deleteClip(Soundboard $soundboard, SoundboardClip $clip): RedirectResponse
    {
        if ($clip->soundboard_id !== $soundboard->id) {
            abort(404);
        }

        if (file_exists(storage_path('app/' . $clip->file_path))) {
            unlink(storage_path('app/' . $clip->file_path));
        }

        $clip->delete();

        return redirect()->back()
            ->with('success', 'Audio clip deleted successfully.');
    }
}

