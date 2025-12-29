<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierTemplate;
use App\Models\AuditLog;
use App\Services\Carrier\CarrierTemplateService;
use App\Services\Carrier\CarrierTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CarrierController extends Controller
{
    public function __construct(
        protected CarrierTemplateService $templateService,
        protected CarrierTestService $testService
    ) {}

    public function index(Request $request): View
    {
        $query = Carrier::withCount('dids')->with('backupCarrier');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $carriers = $query->orderBy('name')->paginate(25);

        return view('settings.carriers.index', compact('carriers'));
    }

    public function create(): View
    {
        $otherCarriers = Carrier::where('type', 'outbound')->pluck('name', 'id');
        
        return view('settings.carriers.create', [
            'types' => Carrier::TYPES,
            'authTypes' => Carrier::AUTH_TYPES,
            'transports' => Carrier::TRANSPORTS,
            'defaultCodecs' => Carrier::DEFAULT_CODECS,
            'otherCarriers' => $otherCarriers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCarrier($request);

        $carrier = Carrier::create($validated);

        AuditLog::log('created', $carrier, null, $carrier->toArray(), 'Carrier created');

        return redirect()->route('carriers.index')
            ->with('success', 'Carrier created successfully.');
    }

    public function show(Carrier $carrier): View
    {
        $carrier->load('dids');

        return view('settings.carriers.show', compact('carrier'));
    }

    public function edit(Carrier $carrier): View
    {
        $otherCarriers = Carrier::where('type', 'outbound')
            ->where('id', '!=', $carrier->id)
            ->pluck('name', 'id');
            
        return view('settings.carriers.edit', [
            'carrier' => $carrier,
            'types' => Carrier::TYPES,
            'authTypes' => Carrier::AUTH_TYPES,
            'transports' => Carrier::TRANSPORTS,
            'defaultCodecs' => Carrier::DEFAULT_CODECS,
            'otherCarriers' => $otherCarriers,
        ]);
    }

    public function update(Request $request, Carrier $carrier): RedirectResponse
    {
        $validated = $this->validateCarrier($request, $carrier);

        $oldValues = $carrier->toArray();
        $carrier->update($validated);

        AuditLog::log('updated', $carrier, $oldValues, $carrier->fresh()->toArray(), 'Carrier updated');

        return redirect()->route('carriers.index')
            ->with('success', 'Carrier updated successfully.');
    }

    public function destroy(Carrier $carrier): RedirectResponse
    {
        $oldValues = $carrier->toArray();
        $carrier->delete();

        AuditLog::log('deleted', $carrier, $oldValues, null, 'Carrier deleted');

        return redirect()->route('carriers.index')
            ->with('success', 'Carrier deleted successfully.');
    }

    public function toggleStatus(Carrier $carrier): RedirectResponse
    {
        $oldValues = $carrier->toArray();
        $carrier->update(['is_active' => !$carrier->is_active]);

        AuditLog::log('updated', $carrier, $oldValues, $carrier->fresh()->toArray(),
            $carrier->is_active ? 'Carrier enabled' : 'Carrier disabled');

        return redirect()->back()
            ->with('success', 'Carrier status updated.');
    }

    /**
     * Quick Setup - Provider selection page
     */
    public function quickSetup(): View
    {
        $providers = $this->templateService->getTemplatesGroupedByProvider();
        $templates = [];
        
        foreach ($providers as $slug => $provider) {
            $templates[$slug] = [
                'name' => $provider['name'],
                'logo' => $provider['logo'],
                'templates' => [],
            ];
            foreach ($provider['templates'] as $direction => $template) {
                $templates[$slug]['templates'][$direction] = $template;
            }
        }

        return view('settings.carriers.quick-setup', [
            'providers' => $providers,
            'templates' => $templates,
        ]);
    }

    /**
     * Quick Setup - Store carrier from template
     */
    public function quickSetupStore(Request $request): JsonResponse
    {
        $request->validate([
            'provider_slug' => 'required|string',
            'direction' => 'required|in:inbound,outbound',
        ]);

        $template = $this->templateService->getTemplate(
            $request->provider_slug,
            $request->direction
        );

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found.',
            ], 404);
        }

        // Validate input based on template requirements
        $authType = $request->auth_type ?? $template->getDefaultAuthType();
        $errors = $this->templateService->validateInput($template, $request->all(), $authType);

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
            ], 422);
        }

        try {
            $carrier = $this->templateService->createCarrierFromTemplate(
                $template,
                $request->all(),
                $request->name ?: null
            );

            AuditLog::log('created', $carrier, null, $carrier->toArray(), 
                "Carrier created via Quick Setup ({$template->provider_name})");

            return response()->json([
                'success' => true,
                'message' => 'Carrier created successfully.',
                'carrier' => $carrier,
                'redirect' => route('carriers.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create carrier: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test carrier connection
     */
    public function testConnection(Carrier $carrier): JsonResponse
    {
        $result = $this->testService->testConnection($carrier);

        return response()->json($result);
    }

    private function validateCarrier(Request $request, ?Carrier $carrier = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:inbound,outbound',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'transport' => 'required|in:udp,tcp,tls',
            'auth_type' => 'required|in:ip,registration',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'from_domain' => 'nullable|string|max:255',
            'from_user' => 'nullable|string|max:255',
            'codecs' => 'nullable|array',
            'max_channels' => 'nullable|integer|min:1',
            'context' => 'required|string|max:255',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'backup_carrier_id' => 'nullable|exists:carriers,id',
        ];

        // Password not required if editing and not changing
        if ($carrier && empty($request->password)) {
            unset($rules['password']);
        }

        $validated = $request->validate($rules);

        if ($carrier && empty($validated['password'])) {
            unset($validated['password']);
        }

        return $validated;
    }
}







