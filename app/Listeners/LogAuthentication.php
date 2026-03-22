<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogAuthentication
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;
        $action = '';
        $description = '';

        if ($event instanceof Login) {
            $action = 'login';
            $description = 'Pengguna berhasil masuk';
        } elseif ($event instanceof Logout) {
            $action = 'logout';
            $description = 'Pengguna keluar';
        }

        if ($user && $action) {
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'action' => $action,
                'description' => $description,
                'subject_type' => get_class($user),
                'subject_id' => $user->id,
                'properties' => [],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
