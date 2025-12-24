<?php

use App\Models\Extension;
use App\Models\Queue;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Private channel for individual users
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Presence channel for dashboard - all authenticated users
 * Shows who is currently viewing the dashboard
 */
Broadcast::channel('dashboard', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'extension' => $user->extension?->extension,
            'role' => $user->roles->first()?->name,
        ];
    }
    return false;
});

/**
 * Channel for extension status updates
 * All authenticated users can listen
 */
Broadcast::channel('extensions', function ($user) {
    return $user !== null;
});

/**
 * Channel for specific extension updates
 */
Broadcast::channel('extension.{extensionId}', function ($user, $extensionId) {
    // User can listen to their own extension or if they have permission
    if ($user->extension_id == $extensionId) {
        return true;
    }
    return $user->can('extensions.view');
});

/**
 * Channel for call events (all calls)
 * Requires call-logs view permission
 */
Broadcast::channel('calls', function ($user) {
    return $user->can('call-logs.view');
});

/**
 * Channel for queue updates
 */
Broadcast::channel('queues', function ($user) {
    return $user->can('queues.view');
});

/**
 * Channel for specific queue updates
 */
Broadcast::channel('queue.{queueId}', function ($user, $queueId) {
    // Check if user is a member of this queue or has permission
    $queue = Queue::find($queueId);
    if (!$queue) {
        return false;
    }
    
    // Check if user's extension is a member of this queue
    if ($user->extension) {
        $isMember = $queue->members()
            ->where('extension_id', $user->extension->id)
            ->exists();
        if ($isMember) {
            return true;
        }
    }
    
    return $user->can('queues.view');
});

/**
 * Channel for agent status updates
 */
Broadcast::channel('agents', function ($user) {
    return $user->can('users.view') || $user->can('queues.view');
});

/**
 * Channel for system notifications
 * All authenticated users
 */
Broadcast::channel('notifications', function ($user) {
    return $user !== null;
});

/**
 * Private channel for user-specific notifications
 */
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
