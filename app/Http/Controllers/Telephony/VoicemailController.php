<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\Voicemail;
use App\Models\Extension;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoicemailController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        
        $query = Voicemail::with('extension');

        // If not admin, only show user's voicemails
        if (!$user->hasRole(['Superadmin', 'Admin', 'Manager'])) {
            $query->whereHas('extension', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($request->filled('extension_id')) {
            $query->where('extension_id', $request->extension_id);
        }

        if ($request->filled('status')) {
            $query->where('is_read', $request->status === 'read');
        }

        $voicemails = $query->latest()->paginate(25);
        $extensions = Extension::active()->orderBy('extension')->get();

        return view('telephony.voicemails.index', compact('voicemails', 'extensions'));
    }

    public function show(Voicemail $voicemail): View
    {
        $this->authorize('view', $voicemail);
        
        $voicemail->markAsRead();

        return view('telephony.voicemails.show', compact('voicemail'));
    }

    public function markAsRead(Voicemail $voicemail): RedirectResponse
    {
        $this->authorize('update', $voicemail);
        
        $voicemail->markAsRead();

        return redirect()->back()
            ->with('success', 'Voicemail marked as read.');
    }

    public function markAsUnread(Voicemail $voicemail): RedirectResponse
    {
        $this->authorize('update', $voicemail);
        
        $voicemail->markAsUnread();

        return redirect()->back()
            ->with('success', 'Voicemail marked as unread.');
    }

    public function forward(Request $request, Voicemail $voicemail): RedirectResponse
    {
        $this->authorize('update', $voicemail);

        $request->validate([
            'extension_id' => 'required|exists:extensions,id',
        ]);

        $toExtension = Extension::findOrFail($request->extension_id);
        $voicemail->forward($toExtension);

        return redirect()->back()
            ->with('success', 'Voicemail forwarded successfully.');
    }

    public function destroy(Voicemail $voicemail): RedirectResponse
    {
        $this->authorize('delete', $voicemail);
        
        $voicemail->delete();

        return redirect()->route('voicemails.index')
            ->with('success', 'Voicemail deleted successfully.');
    }

    public function download(Voicemail $voicemail): BinaryFileResponse
    {
        $this->authorize('view', $voicemail);

        $path = storage_path('app/' . $voicemail->file_path);

        if (!file_exists($path)) {
            abort(404, 'Recording not found');
        }

        return response()->download($path, "voicemail_{$voicemail->id}.wav");
    }
}





