<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| WebPhone API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('webphone')->group(function () {
    // Get credentials for SIP registration
    Route::get('/credentials', [WebPhoneController::class, 'credentials']);
    
    // Update agent status
    Route::post('/status', [WebPhoneController::class, 'updateStatus']);
    
    // Log phone events
    Route::post('/events', [WebPhoneController::class, 'logEvent']);
    
    // Get dial settings
    Route::get('/settings', [WebPhoneController::class, 'dialSettings']);
});

