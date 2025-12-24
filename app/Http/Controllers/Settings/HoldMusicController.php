<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\HoldMusic;
use App\Models\HoldMusicFile;
use App\Models\AuditLog;
use App\Services\Asterisk\AudioConverter;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class HoldMusicController extends Controller
{
    public function index(): View
    {
        $holdMusic = HoldMusic::withCount('files')
            ->orderBy('name')
            ->paginate(25);

        return view('settings.hold-music.index', compact('holdMusic'));
    }

    public function create(): View
    {
        return view('settings.hold-music.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $validated['directory_name'] = Str::slug($validated['name']) . '_' . time();
        $validated['is_active'] = true;

        $holdMusic = HoldMusic::create($validated);

        if ($validated['is_default'] ?? false) {
            $holdMusic->setAsDefault();
        }

        AuditLog::log('created', $holdMusic, null, $holdMusic->toArray(), 'Hold music class created');

        return redirect()->route('hold-music.edit', $holdMusic)
            ->with('success', 'Hold music class created. Now upload audio files.');
    }

    public function show(HoldMusic $holdMusic): View
    {
        $holdMusic->load('files');

        return view('settings.hold-music.show', compact('holdMusic'));
    }

    public function edit(HoldMusic $holdMusic): View
    {
        $holdMusic->load('files');

        return view('settings.hold-music.edit', compact('holdMusic'));
    }

    public function update(Request $request, HoldMusic $holdMusic): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $oldValues = $holdMusic->toArray();
        $holdMusic->update($validated);

        if ($validated['is_default'] ?? false) {
            $holdMusic->setAsDefault();
        }

        AuditLog::log('updated', $holdMusic, $oldValues, $holdMusic->fresh()->toArray(), 'Hold music class updated');

        return redirect()->route('hold-music.index')
            ->with('success', 'Hold music class updated successfully.');
    }

    public function destroy(HoldMusic $holdMusic): RedirectResponse
    {
        $oldValues = $holdMusic->toArray();
        
        // Delete files from storage
        foreach ($holdMusic->files as $file) {
            if (file_exists(storage_path('app/' . $file->file_path))) {
                unlink(storage_path('app/' . $file->file_path));
            }
            if ($file->converted_path && file_exists(storage_path('app/' . $file->converted_path))) {
                unlink(storage_path('app/' . $file->converted_path));
            }
        }

        $holdMusic->delete();

        AuditLog::log('deleted', $holdMusic, $oldValues, null, 'Hold music class deleted');

        return redirect()->route('hold-music.index')
            ->with('success', 'Hold music class deleted successfully.');
    }

    public function uploadFile(Request $request, HoldMusic $holdMusic): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:mp3,wav,ogg|max:20480', // 20MB max
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('hold_music/' . $holdMusic->directory_name);

        // TODO: Convert to Asterisk format using AudioConverter service
        // $convertedPath = app(AudioConverter::class)->convert(storage_path('app/' . $path));

        $holdMusicFile = $holdMusic->files()->create([
            'original_filename' => $originalName,
            'file_path' => $path,
            'converted_path' => null, // Will be set after conversion
            'sort_order' => $holdMusic->files()->count(),
            'is_active' => true,
        ]);

        return redirect()->back()
            ->with('success', 'Audio file uploaded successfully.');
    }

    public function deleteFile(HoldMusic $holdMusic, HoldMusicFile $file): RedirectResponse
    {
        if ($file->hold_music_id !== $holdMusic->id) {
            abort(404);
        }

        // Delete files from storage
        if (file_exists(storage_path('app/' . $file->file_path))) {
            unlink(storage_path('app/' . $file->file_path));
        }
        if ($file->converted_path && file_exists(storage_path('app/' . $file->converted_path))) {
            unlink(storage_path('app/' . $file->converted_path));
        }

        $file->delete();

        return redirect()->back()
            ->with('success', 'Audio file deleted successfully.');
    }
}

