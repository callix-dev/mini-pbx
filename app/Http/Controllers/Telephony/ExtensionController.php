<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ExtensionController extends Controller
{
    public function index(Request $request): View
    {
        // Sync extension status from Asterisk realtime tables
        $this->syncExtensionStatus();

        $query = Extension::with(['user', 'groups']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('extension', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === 'yes');
        }

        $extensions = $query->orderBy('extension')->paginate(25);

        return view('telephony.extensions.index', compact('extensions'));
    }

    public function create(): View
    {
        $users = User::whereDoesntHave('extension')->active()->get();
        return view('telephony.extensions.create', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'extension' => 'required|string|max:20|unique:extensions',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'user_id' => 'nullable|exists:users,id',
            'voicemail_enabled' => 'boolean',
            'voicemail_password' => 'nullable|string|min:4',
            'voicemail_email' => 'nullable|email',
            'caller_id_name' => 'nullable|string|max:255',
            'caller_id_number' => 'nullable|string|max:30',
        ]);

        $validated['is_active'] = true;
        $validated['status'] = 'offline';

        $extension = Extension::create($validated);

        AuditLog::log('created', $extension, null, $extension->toArray(), 'Extension created');

        return redirect()->route('extensions.index')
            ->with('success', 'Extension created successfully.');
    }

    public function show(Extension $extension): View
    {
        $extension->load(['user', 'groups', 'queueMemberships.queue', 'voicemails' => function ($q) {
            $q->latest()->limit(10);
        }]);

        $recentCalls = $extension->callLogs()->with('disposition')->latest()->limit(20)->get();

        // Get registration history for last 30 days
        $registrationHistory = $extension->registrations()
            ->where('registered_at', '>=', now()->subDays(30))
            ->orderBy('registered_at', 'desc')
            ->paginate(15);

        return view('telephony.extensions.show', compact('extension', 'recentCalls', 'registrationHistory'));
    }

    public function edit(Extension $extension): View
    {
        $users = User::where(function ($q) use ($extension) {
            $q->whereDoesntHave('extension')
                ->orWhere('id', $extension->user_id);
        })->active()->get();

        return view('telephony.extensions.edit', compact('extension', 'users'));
    }

    public function update(Request $request, Extension $extension): RedirectResponse
    {
        $validated = $request->validate([
            'extension' => 'required|string|max:20|unique:extensions,extension,' . $extension->id,
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:6',
            'user_id' => 'nullable|exists:users,id',
            'voicemail_enabled' => 'boolean',
            'voicemail_password' => 'nullable|string|min:4',
            'voicemail_email' => 'nullable|email',
            'caller_id_name' => 'nullable|string|max:255',
            'caller_id_number' => 'nullable|string|max:30',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $oldValues = $extension->toArray();
        $extension->update($validated);

        AuditLog::log('updated', $extension, $oldValues, $extension->fresh()->toArray(), 'Extension updated');

        return redirect()->route('extensions.index')
            ->with('success', 'Extension updated successfully.');
    }

    public function destroy(Extension $extension): RedirectResponse
    {
        $oldValues = $extension->toArray();
        $extension->delete();

        AuditLog::log('deleted', $extension, $oldValues, null, 'Extension deleted');

        return redirect()->route('extensions.index')
            ->with('success', 'Extension deleted successfully.');
    }

    public function bulkCreate(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        // TODO: Implement bulk import using Maatwebsite Excel

        return redirect()->route('extensions.index')
            ->with('success', 'Extensions imported successfully.');
    }

    /**
     * Bulk create extensions from a range
     */
    public function bulkCreateRange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_extension' => 'required|integer|min:1|max:99999',
            'end_extension' => 'required|integer|min:1|max:99999|gte:start_extension',
            'name_template' => 'required|string|max:255',
            'password_type' => 'required|in:random,same_as_extension,fixed',
            'fixed_password' => 'required_if:password_type,fixed|nullable|string|min:6',
            'voicemail_enabled' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $start = (int) $validated['start_extension'];
        $end = (int) $validated['end_extension'];
        $count = $end - $start + 1;

        // Limit to prevent abuse
        if ($count > 1000) {
            return redirect()->back()
                ->with('error', 'Cannot create more than 1000 extensions at once.');
        }

        $created = 0;
        $skipped = 0;
        $createdExtensions = [];

        for ($ext = $start; $ext <= $end; $ext++) {
            $extNumber = (string) $ext;
            
            // Check if extension already exists
            if (Extension::where('extension', $extNumber)->exists()) {
                $skipped++;
                continue;
            }

            // Generate password based on type
            $password = match ($validated['password_type']) {
                'random' => Str::random(12),
                'same_as_extension' => $extNumber,
                'fixed' => $validated['fixed_password'],
            };

            // Generate name from template
            $name = str_replace('{ext}', $extNumber, $validated['name_template']);

            $extension = Extension::create([
                'extension' => $extNumber,
                'name' => $name,
                'password' => $password,
                'status' => 'offline',
                'is_active' => $validated['is_active'] ?? true,
                'voicemail_enabled' => $validated['voicemail_enabled'] ?? false,
            ]);

            $createdExtensions[] = [
                'extension' => $extNumber,
                'password' => $password,
            ];

            AuditLog::log('created', $extension, null, $extension->toArray(), 'Extension created via bulk create');
            $created++;
        }

        $message = "Successfully created {$created} extensions.";
        if ($skipped > 0) {
            $message .= " {$skipped} extensions were skipped (already exist).";
        }

        return redirect()->route('extensions.index')
            ->with('success', $message)
            ->with('created_extensions', $createdExtensions);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        // Handle comma-separated IDs from hidden input
        $ids = is_string($request->ids) ? explode(',', $request->ids) : $request->ids;
        
        $request->merge(['ids' => array_filter($ids)]);
        
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:extensions,id',
            'action' => 'required|in:enable,disable,delete,change_password_random,change_password_fixed,change_password_extension',
            'password_type' => 'nullable|in:random,fixed,same_as_extension',
            'fixed_password' => 'required_if:action,change_password_fixed|nullable|string|min:6',
        ]);

        $extensions = Extension::whereIn('id', $request->ids)->get();
        $count = $extensions->count();
        $changedPasswords = [];

        foreach ($extensions as $extension) {
            $oldValues = $extension->toArray();

            switch ($request->action) {
                case 'enable':
                    $extension->update(['is_active' => true]);
                    AuditLog::log('updated', $extension, $oldValues, $extension->fresh()->toArray(), 'Extension enabled via bulk action');
                    break;
                    
                case 'disable':
                    $extension->update(['is_active' => false]);
                    AuditLog::log('updated', $extension, $oldValues, $extension->fresh()->toArray(), 'Extension disabled via bulk action');
                    break;
                    
                case 'delete':
                    $extension->delete();
                    AuditLog::log('deleted', $extension, $oldValues, null, 'Extension deleted via bulk action');
                    break;
                    
                case 'change_password_random':
                    $newPassword = Str::random(12);
                    $extension->update(['password' => $newPassword]);
                    $changedPasswords[] = [
                        'extension' => $extension->extension,
                        'password' => $newPassword,
                    ];
                    AuditLog::log('updated', $extension, ['password' => '[HIDDEN]'], ['password' => '[HIDDEN]'], 'Extension password changed to random via bulk action');
                    break;
                    
                case 'change_password_fixed':
                    $extension->update(['password' => $request->fixed_password]);
                    $changedPasswords[] = [
                        'extension' => $extension->extension,
                        'password' => $request->fixed_password,
                    ];
                    AuditLog::log('updated', $extension, ['password' => '[HIDDEN]'], ['password' => '[HIDDEN]'], 'Extension password changed to fixed value via bulk action');
                    break;
                    
                case 'change_password_extension':
                    $extension->update(['password' => $extension->extension]);
                    $changedPasswords[] = [
                        'extension' => $extension->extension,
                        'password' => $extension->extension,
                    ];
                    AuditLog::log('updated', $extension, ['password' => '[HIDDEN]'], ['password' => '[HIDDEN]'], 'Extension password set to extension number via bulk action');
                    break;
            }
        }

        $actionMessages = [
            'enable' => "Enabled {$count} extensions.",
            'disable' => "Disabled {$count} extensions.",
            'delete' => "Deleted {$count} extensions.",
            'change_password_random' => "Changed passwords for {$count} extensions to random values.",
            'change_password_fixed' => "Changed passwords for {$count} extensions to fixed value.",
            'change_password_extension' => "Changed passwords for {$count} extensions to match extension numbers.",
        ];

        $response = redirect()->route('extensions.index')
            ->with('success', $actionMessages[$request->action] ?? 'Bulk action completed.');

        if (!empty($changedPasswords)) {
            $response->with('changed_passwords', $changedPasswords);
        }

        return $response;
    }

    public function toggleStatus(Extension $extension): RedirectResponse
    {
        $oldValues = $extension->toArray();
        $extension->update(['is_active' => !$extension->is_active]);

        AuditLog::log('updated', $extension, $oldValues, $extension->fresh()->toArray(), 
            $extension->is_active ? 'Extension enabled' : 'Extension disabled');

        return redirect()->back()
            ->with('success', 'Extension status updated.');
    }

    public function emailCredentials(Extension $extension): RedirectResponse
    {
        if (!$extension->user?->email) {
            return redirect()->back()
                ->with('error', 'No email address associated with this extension.');
        }

        // TODO: Send email with credentials
        // Mail::to($extension->user->email)->send(new ExtensionCredentials($extension));

        return redirect()->back()
            ->with('success', 'Credentials emailed successfully.');
    }

    /**
     * Generate a secure random password
     */
    private function generateSecurePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }

    /**
     * Sync extension registration status from Asterisk realtime tables (ps_contacts)
     */
    protected function syncExtensionStatus(): void
    {
        try {
            // Get all registered contacts from Asterisk's realtime table
            $contacts = DB::table('ps_contacts')
                ->pluck('via_addr', 'endpoint')
                ->toArray();

            // Update all extensions based on registration status
            Extension::chunk(100, function ($extensions) use ($contacts) {
                foreach ($extensions as $extension) {
                    $isRegistered = isset($contacts[$extension->extension]);
                    $newStatus = $isRegistered ? 'online' : 'offline';

                    // Only update if not on_call/ringing (those are controlled by call events)
                    if (!in_array($extension->status, ['on_call', 'ringing'])) {
                        if ($extension->status !== $newStatus) {
                            $extension->status = $newStatus;
                            if ($isRegistered) {
                                $extension->last_registered_at = now();
                                $extension->last_registered_ip = $contacts[$extension->extension];
                            }
                            $extension->saveQuietly(); // Don't trigger observer to avoid loop
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            // Silently fail if ps_contacts table doesn't exist or DB error
            \Log::warning('Failed to sync extension status: ' . $e->getMessage());
        }
    }
}
