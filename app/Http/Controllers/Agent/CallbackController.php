<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Callback;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CallbackController extends Controller
{
    public function index(Request $request): View
    {
        $query = Callback::with('callLog')
            ->forUser(auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $callbacks = $query->orderBy('scheduled_at')->paginate(25);
        $statuses = Callback::STATUSES;

        // Get counts
        $pendingCount = Callback::forUser(auth()->id())->pending()->count();
        $dueCount = Callback::forUser(auth()->id())->due()->count();

        return view('agent.callbacks.index', compact('callbacks', 'statuses', 'pendingCount', 'dueCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:30',
            'caller_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'priority' => 'nullable|in:normal,high,urgent',
            'call_log_id' => 'nullable|exists:call_logs,id',
        ]);

        // Combine date and time
        $scheduledAt = \Carbon\Carbon::parse($validated['scheduled_date'] . ' ' . $validated['scheduled_time']);
        
        Callback::create([
            'user_id' => auth()->id(),
            'phone_number' => $validated['phone_number'],
            'contact_name' => $validated['caller_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'scheduled_at' => $scheduledAt,
            'call_log_id' => $validated['call_log_id'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->back()
            ->with('success', 'Callback scheduled successfully for ' . $scheduledAt->format('M d, Y \a\t h:i A'));
    }

    public function complete(Callback $callback): RedirectResponse
    {
        $this->authorize('update', $callback);

        $callback->complete();

        return redirect()->back()
            ->with('success', 'Callback marked as completed.');
    }

    public function cancel(Callback $callback): RedirectResponse
    {
        $this->authorize('update', $callback);

        $callback->cancel();

        return redirect()->back()
            ->with('success', 'Callback cancelled.');
    }

    public function destroy(Callback $callback): RedirectResponse
    {
        $this->authorize('delete', $callback);

        $callback->delete();

        return redirect()->back()
            ->with('success', 'Callback deleted.');
    }
}

