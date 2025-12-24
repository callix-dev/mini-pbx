<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Extension;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['roles', 'extension']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('name')->paginate(25);
        $roles = Role::all();

        return view('platform.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::all();
        $extensions = Extension::whereDoesntHave('user')->active()->get();

        return view('platform.users.create', compact('roles', 'extensions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|exists:roles,name',
            'extension_id' => 'nullable|exists:extensions,id',
            'timezone' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'extension_id' => $validated['extension_id'],
            'timezone' => $validated['timezone'] ?? 'UTC',
            'is_active' => true,
            'agent_status' => 'offline',
        ]);

        $user->assignRole($validated['role']);

        AuditLog::log('created', $user, null, $user->toArray(), 'User created');

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $user->load(['roles', 'extension', 'agentBreaks' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return view('platform.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $roles = Role::all();
        $extensions = Extension::where(function ($q) use ($user) {
            $q->whereDoesntHave('user')
                ->orWhere('id', $user->extension_id);
        })->active()->get();

        return view('platform.users.edit', compact('user', 'roles', 'extensions'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|exists:roles,name',
            'extension_id' => 'nullable|exists:extensions,id',
            'timezone' => 'nullable|string|max:50',
        ]);

        $oldValues = $user->toArray();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'extension_id' => $validated['extension_id'],
            'timezone' => $validated['timezone'] ?? 'UTC',
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$validated['role']]);

        AuditLog::log('updated', $user, $oldValues, $user->fresh()->toArray(), 'User updated');

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account.');
        }

        $oldValues = $user->toArray();
        $user->delete();

        AuditLog::log('deleted', $user, $oldValues, null, 'User deleted');

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'You cannot deactivate your own account.');
        }

        $oldValues = $user->toArray();
        $user->update(['is_active' => !$user->is_active]);

        AuditLog::log('updated', $user, $oldValues, $user->fresh()->toArray(),
            $user->is_active ? 'User activated' : 'User deactivated');

        return redirect()->back()
            ->with('success', 'User status updated.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $newPassword = \Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        // TODO: Send password reset email
        // Mail::to($user)->send(new PasswordReset($newPassword));

        AuditLog::log('password_changed', $user, null, null, 'Password reset by admin');

        return redirect()->back()
            ->with('success', "Password reset successfully. New password: {$newPassword}");
    }
}

