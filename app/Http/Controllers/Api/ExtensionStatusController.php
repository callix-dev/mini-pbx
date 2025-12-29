<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Services\Asterisk\PjsipRealtimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtensionStatusController extends Controller
{
    /**
     * Get live registration status for all extensions from Asterisk
     */
    public function index(): JsonResponse
    {
        // Get all registered contacts from Asterisk's realtime table
        $contacts = DB::table('ps_contacts')
            ->select('endpoint', 'uri', 'user_agent', 'via_addr', 'via_port', 'expiration_time')
            ->get()
            ->keyBy('endpoint');

        // Get all extensions with their current status
        $extensions = Extension::with('user:id,name')
            ->select('id', 'extension', 'name', 'status', 'last_registered_at', 'last_registered_ip', 'user_id')
            ->get()
            ->map(function ($ext) use ($contacts) {
                $contact = $contacts->get($ext->extension);
                $isRegistered = $contact !== null;
                
                // Determine actual status
                $liveStatus = $isRegistered ? 'online' : 'offline';
                
                // Update the extension status in DB if it changed
                if ($ext->status !== $liveStatus && $ext->status !== 'on_call' && $ext->status !== 'ringing') {
                    $ext->status = $liveStatus;
                    if ($isRegistered && $contact) {
                        $ext->last_registered_at = now();
                        $ext->last_registered_ip = $contact->via_addr;
                    }
                    $ext->save();
                }

                return [
                    'id' => $ext->id,
                    'extension' => $ext->extension,
                    'name' => $ext->name,
                    'status' => $ext->status,
                    'is_registered' => $isRegistered,
                    'user' => $ext->user ? [
                        'id' => $ext->user->id,
                        'name' => $ext->user->name,
                    ] : null,
                    'contact' => $isRegistered ? [
                        'uri' => $contact->uri,
                        'user_agent' => $contact->user_agent,
                        'ip' => $contact->via_addr,
                        'port' => $contact->via_port,
                    ] : null,
                    'last_registered_at' => $ext->last_registered_at?->toIso8601String(),
                    'last_registered_ip' => $ext->last_registered_ip,
                ];
            });

        return response()->json([
            'success' => true,
            'extensions' => $extensions,
            'summary' => [
                'total' => $extensions->count(),
                'online' => $extensions->where('is_registered', true)->count(),
                'offline' => $extensions->where('is_registered', false)->count(),
            ],
        ]);
    }

    /**
     * Get status for a single extension
     */
    public function show(string $extension): JsonResponse
    {
        $ext = Extension::where('extension', $extension)->first();

        if (!$ext) {
            return response()->json([
                'success' => false,
                'message' => 'Extension not found',
            ], 404);
        }

        // Check if registered in Asterisk
        $contact = DB::table('ps_contacts')
            ->where('endpoint', $extension)
            ->first();

        $isRegistered = $contact !== null;

        return response()->json([
            'success' => true,
            'extension' => [
                'id' => $ext->id,
                'extension' => $ext->extension,
                'name' => $ext->name,
                'status' => $isRegistered ? 'online' : 'offline',
                'is_registered' => $isRegistered,
                'contact' => $isRegistered ? [
                    'uri' => $contact->uri,
                    'user_agent' => $contact->user_agent,
                    'ip' => $contact->via_addr,
                    'port' => $contact->via_port,
                ] : null,
            ],
        ]);
    }

    /**
     * Sync all extension statuses from Asterisk realtime tables
     */
    public function sync(): JsonResponse
    {
        $contacts = DB::table('ps_contacts')
            ->pluck('via_addr', 'endpoint')
            ->toArray();

        $updated = 0;

        Extension::chunk(100, function ($extensions) use ($contacts, &$updated) {
            foreach ($extensions as $extension) {
                $isRegistered = isset($contacts[$extension->extension]);
                $newStatus = $isRegistered ? 'online' : 'offline';

                // Only update if not on call/ringing (those are controlled by call events)
                if ($extension->status !== 'on_call' && $extension->status !== 'ringing') {
                    if ($extension->status !== $newStatus) {
                        $extension->status = $newStatus;
                        if ($isRegistered) {
                            $extension->last_registered_at = now();
                            $extension->last_registered_ip = $contacts[$extension->extension];
                        }
                        $extension->save();
                        $updated++;
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Synced extension statuses",
            'updated' => $updated,
        ]);
    }
}







