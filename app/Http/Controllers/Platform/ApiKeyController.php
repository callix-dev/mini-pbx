<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function index(): View
    {
        $apiKeys = ApiKey::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('platform.api-keys.index', compact('apiKeys'));
    }

    public function create(): View
    {
        return view('platform.api-keys.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'ip_whitelist' => 'nullable|string',
            'rate_limit' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $ipWhitelist = null;
        if (!empty($validated['ip_whitelist'])) {
            $ipWhitelist = array_filter(array_map('trim', explode(',', $validated['ip_whitelist'])));
        }

        $result = ApiKey::generate(
            auth()->user(),
            $validated['name'],
            $validated['permissions'] ?? [],
            $ipWhitelist
        );

        if (!empty($validated['rate_limit'])) {
            $result['api_key']->update(['rate_limit' => $validated['rate_limit']]);
        }

        if (!empty($validated['expires_at'])) {
            $result['api_key']->update(['expires_at' => $validated['expires_at']]);
        }

        AuditLog::log('created', $result['api_key'], null, ['name' => $validated['name']], 'API key created');

        return redirect()->route('api-keys.index')
            ->with('success', 'API Key created successfully.')
            ->with('new_key', $result['key'])
            ->with('new_secret', $result['secret']);
    }

    public function show(ApiKey $apiKey): View
    {
        $apiKey->load(['user', 'logs' => function ($q) {
            $q->latest()->limit(50);
        }]);

        return view('platform.api-keys.show', compact('apiKey'));
    }

    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        $oldValues = ['name' => $apiKey->name];
        $apiKey->delete();

        AuditLog::log('deleted', $apiKey, $oldValues, null, 'API key deleted');

        return redirect()->route('api-keys.index')
            ->with('success', 'API key deleted successfully.');
    }

    public function toggleStatus(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->update(['is_active' => !$apiKey->is_active]);

        AuditLog::log('updated', $apiKey, null, null,
            $apiKey->is_active ? 'API key activated' : 'API key deactivated');

        return redirect()->back()
            ->with('success', 'API key status updated.');
    }

    public function regenerate(ApiKey $apiKey): RedirectResponse
    {
        $newSecret = \Str::random(64);
        $apiKey->update(['secret_hash' => bcrypt($newSecret)]);

        AuditLog::log('updated', $apiKey, null, null, 'API key secret regenerated');

        return redirect()->back()
            ->with('success', 'API key secret regenerated.')
            ->with('new_secret', $newSecret);
    }
}


