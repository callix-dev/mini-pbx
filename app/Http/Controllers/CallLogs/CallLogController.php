<?php

namespace App\Http\Controllers\CallLogs;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\Disposition;
use App\Models\Extension;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CallLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = CallLog::with(['extension', 'queue', 'disposition']);

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        // Filter by extension
        if ($request->filled('extension_id')) {
            $query->where('extension_id', $request->extension_id);
        }

        // Filter by queue
        if ($request->filled('queue_id')) {
            $query->where('queue_id', $request->queue_id);
        }

        // Search by caller/callee ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller_id', 'like', "%{$search}%")
                    ->orWhere('callee_id', 'like', "%{$search}%")
                    ->orWhere('caller_name', 'like', "%{$search}%");
            });
        }

        $callLogs = $query->latest('start_time')->paginate(50);
        $extensions = Extension::active()->orderBy('extension')->get();
        $queues = Queue::active()->orderBy('display_name')->get();
        $types = CallLog::TYPES;
        $statuses = CallLog::STATUSES;

        return view('call-logs.index', compact('callLogs', 'extensions', 'queues', 'types', 'statuses'));
    }

    public function show(CallLog $callLog): View
    {
        $callLog->load(['extension', 'queue', 'carrier', 'disposition', 'callNotes.user']);

        return view('call-logs.show', compact('callLog'));
    }

    public function addNote(Request $request, CallLog $callLog): RedirectResponse
    {
        $request->validate([
            'note' => 'required|string|max:1000',
            'is_private' => 'boolean',
        ]);

        $callLog->callNotes()->create([
            'user_id' => auth()->id(),
            'note' => $request->note,
            'is_private' => $request->is_private ?? false,
        ]);

        return redirect()->back()
            ->with('success', 'Note added successfully.');
    }

    public function updateDisposition(Request $request, CallLog $callLog): RedirectResponse
    {
        $request->validate([
            'disposition_id' => 'required|exists:dispositions,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $callLog->update([
            'disposition_id' => $request->disposition_id,
            'notes' => $request->notes,
        ]);

        // Check if disposition requires callback
        $disposition = Disposition::find($request->disposition_id);
        if ($disposition->requires_callback && $request->filled('callback_at')) {
            auth()->user()->callbacks()->create([
                'call_log_id' => $callLog->id,
                'phone_number' => $callLog->caller_id,
                'contact_name' => $callLog->caller_name,
                'scheduled_at' => $request->callback_at,
                'notes' => $request->notes,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Disposition updated successfully.');
    }

    public function playRecording(CallLog $callLog): BinaryFileResponse
    {
        if (!$callLog->hasRecording()) {
            abort(404, 'Recording not found');
        }

        $path = $this->resolveRecordingPath($callLog->recording_path);

        if (!$path || !file_exists($path)) {
            abort(404, 'Recording file not found');
        }

        // Detect content type based on extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentType = match($extension) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'gsm' => 'audio/gsm',
            'ogg' => 'audio/ogg',
            default => 'audio/wav',
        };

        return response()->file($path, [
            'Content-Type' => $contentType,
            'Accept-Ranges' => 'bytes', // Enable seeking in audio player
        ]);
    }

    public function downloadRecording(CallLog $callLog): BinaryFileResponse
    {
        if (!$callLog->hasRecording()) {
            abort(404, 'Recording not found');
        }

        $path = $this->resolveRecordingPath($callLog->recording_path);

        if (!$path || !file_exists($path)) {
            abort(404, 'Recording file not found');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'wav';
        $filename = "recording_{$callLog->uniqueid}_" . date('Y-m-d_His', strtotime($callLog->start_time)) . ".{$extension}";

        return response()->download($path, $filename);
    }

    /**
     * Resolve recording path - checks multiple locations
     */
    private function resolveRecordingPath(string $recordingPath): ?string
    {
        // 1. Try as absolute path (direct from Asterisk)
        if (str_starts_with($recordingPath, '/') && file_exists($recordingPath)) {
            return $recordingPath;
        }

        // 2. Try in storage/app
        $storagePath = storage_path('app/' . $recordingPath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        // 3. Try in storage/app/recordings (symlinked from Asterisk)
        $storageRecordingsPath = storage_path('app/recordings/' . basename($recordingPath));
        if (file_exists($storageRecordingsPath)) {
            return $storageRecordingsPath;
        }

        // 4. Try in configured Asterisk recording path
        $asteriskPath = config('asterisk.recordings.path', '/var/spool/asterisk/monitor');
        $fullPath = rtrim($asteriskPath, '/') . '/' . $recordingPath;
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        // 5. Just try the filename in the Asterisk directory
        $asteriskFilePath = rtrim($asteriskPath, '/') . '/' . basename($recordingPath);
        if (file_exists($asteriskFilePath)) {
            return $asteriskFilePath;
        }

        return null;
    }

    public function export(Request $request): StreamedResponse
    {
        $query = CallLog::with(['extension', 'queue', 'disposition']);

        // Apply same filters as index
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $callLogs = $query->latest('start_time')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="call_logs_' . date('Y-m-d_His') . '.csv"',
        ];

        return response()->stream(function () use ($callLogs) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Date/Time', 'Type', 'Direction', 'Caller ID', 'Caller Name',
                'Called Number', 'Extension', 'Queue', 'Status', 'Duration',
                'Wait Time', 'Disposition', 'Notes'
            ]);

            foreach ($callLogs as $log) {
                fputcsv($handle, [
                    $log->start_time->format('Y-m-d H:i:s'),
                    $log->type,
                    $log->direction,
                    $log->caller_id,
                    $log->caller_name,
                    $log->callee_id,
                    $log->extension?->extension,
                    $log->queue?->display_name,
                    $log->status,
                    $log->formatted_duration,
                    $log->wait_time,
                    $log->disposition?->name,
                    $log->notes,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}

