<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExtensionStatusController;
use App\Http\Controllers\Api\WebPhoneController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::middleware('auth:web')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['web', 'auth'])->prefix('user')->group(function () {
    // Get current user status
    Route::get('/status', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'status' => $user->agent_status ?? 'offline',
            'extension' => $user->extension ? [
                'extension' => $user->extension->extension,
                'status' => $user->extension->status,
            ] : null,
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| WebPhone API Routes
|--------------------------------------------------------------------------
|
| Using 'web' middleware for session-based auth (shares session with main app)
|
*/

Route::middleware(['web', 'auth'])->prefix('webphone')->group(function () {
    // Get credentials for SIP registration
    Route::get('/credentials', [WebPhoneController::class, 'credentials']);
    
    // Update agent status
    Route::post('/status', [WebPhoneController::class, 'updateStatus']);
    
    // Log phone events
    Route::post('/events', [WebPhoneController::class, 'logEvent']);
    
    // Get dial settings
    Route::get('/settings', [WebPhoneController::class, 'dialSettings']);
});

/*
|--------------------------------------------------------------------------
| Extension Status API Routes
|--------------------------------------------------------------------------
|
| Live extension registration status from Asterisk
|
*/

Route::middleware(['web', 'auth'])->prefix('extensions')->group(function () {
    // Get live status for all extensions
    Route::get('/status', [ExtensionStatusController::class, 'index']);
    
    // Get status for a single extension
    Route::get('/status/{extension}', [ExtensionStatusController::class, 'show']);
    
    // Sync all statuses from Asterisk
    Route::post('/sync-status', [ExtensionStatusController::class, 'sync']);
});

/*
|--------------------------------------------------------------------------
| Dashboard API Routes
|--------------------------------------------------------------------------
|
| Real-time dashboard statistics
|
*/

Route::middleware(['web', 'auth'])->prefix('dashboard')->group(function () {
    // Get dashboard stats (active calls, extensions, etc.)
    Route::get('/stats', [DashboardController::class, 'stats']);
    
    // Reset stale extension statuses
    Route::post('/reset-stale', [DashboardController::class, 'resetStaleStatuses']);
});

