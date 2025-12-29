<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Telephony\ExtensionController;
use App\Http\Controllers\Telephony\ExtensionGroupController;
use App\Http\Controllers\Telephony\DidController;
use App\Http\Controllers\Telephony\QueueController;
use App\Http\Controllers\Telephony\RingTreeController;
use App\Http\Controllers\Telephony\IvrController;
use App\Http\Controllers\Telephony\VoicemailController;
use App\Http\Controllers\Telephony\BlockFilterController;
use App\Http\Controllers\CallLogs\CallLogController;
use App\Http\Controllers\CallLogs\AnalyticsController;
use App\Http\Controllers\Settings\CarrierController;
use App\Http\Controllers\Settings\BreakCodeController;
use App\Http\Controllers\Settings\HoldMusicController;
use App\Http\Controllers\Settings\SoundboardController;
use App\Http\Controllers\Settings\DispositionController;
use App\Http\Controllers\Platform\UserController;
use App\Http\Controllers\Platform\SystemSettingController;
use App\Http\Controllers\Platform\ApiKeyController;
use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\BackupController;
use App\Http\Controllers\Agent\CallbackController;
use App\Http\Controllers\SoftphoneController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Softphone (popup window)
    Route::get('/softphone', [SoftphoneController::class, 'index'])->name('softphone');

    // Agent Callbacks
    Route::prefix('callbacks')->name('callbacks.')->group(function () {
        Route::get('/', [CallbackController::class, 'index'])->name('index');
        Route::post('/', [CallbackController::class, 'store'])->name('store');
        Route::patch('/{callback}/complete', [CallbackController::class, 'complete'])->name('complete');
        Route::patch('/{callback}/cancel', [CallbackController::class, 'cancel'])->name('cancel');
        Route::delete('/{callback}', [CallbackController::class, 'destroy'])->name('destroy');
    });

    // Telephony Routes
    Route::prefix('telephony')->group(function () {
        // Extensions
        Route::resource('extensions', ExtensionController::class);
        Route::post('extensions/bulk-create', [ExtensionController::class, 'bulkCreate'])->name('extensions.bulk-create');
        Route::post('extensions/bulk-create-range', [ExtensionController::class, 'bulkCreateRange'])->name('extensions.bulk-create-range');
        Route::post('extensions/bulk-action', [ExtensionController::class, 'bulkAction'])->name('extensions.bulk-action');
        Route::post('extensions/{extension}/email-credentials', [ExtensionController::class, 'emailCredentials'])->name('extensions.email-credentials');
        Route::patch('extensions/{extension}/toggle-status', [ExtensionController::class, 'toggleStatus'])->name('extensions.toggle-status');

        // Extension Groups
        Route::resource('extension-groups', ExtensionGroupController::class);

        // DIDs
        Route::resource('dids', DidController::class);
        Route::post('dids/bulk-create', [DidController::class, 'bulkCreate'])->name('dids.bulk-create');
        Route::post('dids/import', [DidController::class, 'import'])->name('dids.import');
        Route::post('dids/bulk-action', [DidController::class, 'bulkAction'])->name('dids.bulk-action');

        // Queues
        Route::resource('queues', QueueController::class);
        Route::post('queues/{queue}/agents', [QueueController::class, 'updateAgents'])->name('queues.update-agents');
        Route::post('queues/{queue}/vip-callers', [QueueController::class, 'updateVipCallers'])->name('queues.update-vip-callers');

        // Ring Trees
        Route::resource('ring-trees', RingTreeController::class);

        // IVRs
        Route::resource('ivrs', IvrController::class);
        Route::post('ivrs/{ivr}/nodes', [IvrController::class, 'saveNodes'])->name('ivrs.save-nodes');

        // Voicemails
        Route::prefix('voicemails')->name('voicemails.')->group(function () {
            Route::get('/', [VoicemailController::class, 'index'])->name('index');
            Route::get('/{voicemail}', [VoicemailController::class, 'show'])->name('show');
            Route::patch('/{voicemail}/read', [VoicemailController::class, 'markAsRead'])->name('mark-read');
            Route::patch('/{voicemail}/unread', [VoicemailController::class, 'markAsUnread'])->name('mark-unread');
            Route::post('/{voicemail}/forward', [VoicemailController::class, 'forward'])->name('forward');
            Route::delete('/{voicemail}', [VoicemailController::class, 'destroy'])->name('destroy');
            Route::get('/{voicemail}/download', [VoicemailController::class, 'download'])->name('download');
        });

        // Block Filters
        Route::resource('block-filters', BlockFilterController::class);
    });

    // Call Logs Routes
    Route::prefix('call-logs')->name('call-logs.')->group(function () {
        Route::get('/', [CallLogController::class, 'index'])->name('index');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/export', [CallLogController::class, 'export'])->name('export');
        Route::get('/{callLog}', [CallLogController::class, 'show'])->name('show');
        Route::post('/{callLog}/notes', [CallLogController::class, 'addNote'])->name('add-note');
        Route::patch('/{callLog}/disposition', [CallLogController::class, 'updateDisposition'])->name('update-disposition');
        Route::get('/{callLog}/recording', [CallLogController::class, 'playRecording'])->name('play-recording');
        Route::get('/{callLog}/download-recording', [CallLogController::class, 'downloadRecording'])->name('download-recording');
    });

    // Settings Routes
    Route::prefix('settings')->group(function () {
        // Carriers
        Route::resource('carriers', CarrierController::class);
        Route::patch('carriers/{carrier}/toggle-status', [CarrierController::class, 'toggleStatus'])->name('carriers.toggle-status');
        Route::get('carriers-quick-setup', [CarrierController::class, 'quickSetup'])->name('carriers.quick-setup');
        Route::post('carriers-quick-setup', [CarrierController::class, 'quickSetupStore'])->name('carriers.quick-setup.store');
        Route::post('carriers/{carrier}/test-connection', [CarrierController::class, 'testConnection'])->name('carriers.test-connection');

        // Break Codes
        Route::resource('break-codes', BreakCodeController::class);

        // Hold Music
        Route::resource('hold-music', HoldMusicController::class);
        Route::post('hold-music/{holdMusic}/files', [HoldMusicController::class, 'uploadFile'])->name('hold-music.upload-file');
        Route::delete('hold-music/{holdMusic}/files/{holdMusicFile}', [HoldMusicController::class, 'deleteFile'])->name('hold-music.delete-file');

        // Soundboards
        Route::resource('soundboards', SoundboardController::class);
        Route::post('soundboards/{soundboard}/clips', [SoundboardController::class, 'uploadClip'])->name('soundboards.upload-clip');
        Route::delete('soundboards/{soundboard}/clips/{clip}', [SoundboardController::class, 'deleteClip'])->name('soundboards.delete-clip');

        // Dispositions
        Route::resource('dispositions', DispositionController::class);
        Route::post('dispositions/reorder', [DispositionController::class, 'reorder'])->name('dispositions.reorder');
    });

    // Platform Routes (Admin only)
    Route::prefix('platform')->middleware('can:manage-platform')->group(function () {
        // User Management
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        // System Settings
        Route::prefix('system-settings')->name('system-settings.')->group(function () {
            Route::get('/', [SystemSettingController::class, 'index'])->name('index');
            Route::post('/', [SystemSettingController::class, 'update'])->name('update');
            Route::get('/test-ami', [SystemSettingController::class, 'testAmi'])->name('test-ami');
            Route::get('/test-ari', [SystemSettingController::class, 'testAri'])->name('test-ari');
        });

        // API Keys
        Route::resource('api-keys', ApiKeyController::class)->except(['edit', 'update']);
        Route::patch('api-keys/{apiKey}/toggle-status', [ApiKeyController::class, 'toggleStatus'])->name('api-keys.toggle-status');
        Route::post('api-keys/{apiKey}/regenerate', [ApiKeyController::class, 'regenerate'])->name('api-keys.regenerate');

        // Audit Logs
        Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])->name('index');
            Route::get('/export', [AuditLogController::class, 'export'])->name('export');
            Route::get('/{auditLog}', [AuditLogController::class, 'show'])->name('show');
        });

        // Backups
        Route::prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::post('/', [BackupController::class, 'create'])->name('create');
            Route::get('/{backup}/download', [BackupController::class, 'download'])->name('download');
            Route::post('/{backup}/restore', [BackupController::class, 'restore'])->name('restore');
            Route::delete('/{backup}', [BackupController::class, 'destroy'])->name('destroy');
        });

        // System Health
        Route::get('system-health', [\App\Http\Controllers\Platform\SystemHealthController::class, 'index'])->name('system-health.index');
    });
});

require __DIR__.'/auth.php';
