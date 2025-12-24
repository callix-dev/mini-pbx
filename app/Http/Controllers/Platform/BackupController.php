<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = Backup::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('platform.backups.index', compact('backups'));
    }

    public function create(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Create backup record
        $backup = Backup::create([
            'name' => $request->name ?? 'Backup ' . now()->format('Y-m-d H:i:s'),
            'file_path' => '',
            'file_size' => 0,
            'type' => 'manual',
            'status' => 'pending',
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        // TODO: Dispatch backup job
        // BackupJob::dispatch($backup);

        // For now, simulate completion
        $backup->update([
            'status' => 'completed',
            'completed_at' => now(),
            'file_path' => 'backups/' . $backup->id . '.zip',
            'file_size' => 0,
        ]);

        AuditLog::log('created', $backup, null, $backup->toArray(), 'Backup created');

        return redirect()->route('backups.index')
            ->with('success', 'Backup created successfully.');
    }

    public function download(Backup $backup): BinaryFileResponse
    {
        if ($backup->status !== 'completed') {
            abort(404, 'Backup not ready for download');
        }

        $path = storage_path('app/' . $backup->file_path);

        if (!file_exists($path)) {
            abort(404, 'Backup file not found');
        }

        return response()->download($path, $backup->name . '.zip');
    }

    public function restore(Backup $backup): RedirectResponse
    {
        if ($backup->status !== 'completed') {
            return redirect()->back()
                ->with('error', 'Cannot restore from incomplete backup.');
        }

        // TODO: Implement restore logic
        // RestoreBackupJob::dispatch($backup);

        AuditLog::log('restored', $backup, null, null, 'System restored from backup');

        return redirect()->route('backups.index')
            ->with('success', 'Restore initiated. This may take a few minutes.');
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        // Delete file
        $path = storage_path('app/' . $backup->file_path);
        if (file_exists($path)) {
            unlink($path);
        }

        $oldValues = $backup->toArray();
        $backup->delete();

        AuditLog::log('deleted', $backup, $oldValues, null, 'Backup deleted');

        return redirect()->route('backups.index')
            ->with('success', 'Backup deleted successfully.');
    }
}

