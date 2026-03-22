<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Boot the trait.
     */
    protected static function bootLogsActivity()
    {
        // Log Creation
        static::created(function ($model) {
            $user = Auth::user();
            ActivityLog::create([
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'System',
                'action' => 'created',
                'description' => 'Menambahkan data baru',
                'subject_type' => get_class($model),
                'subject_id' => $model->id,
                'properties' => ['attributes' => $model->getAttributes()],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        // Log Update
        static::updated(function ($model) {
            $user = Auth::user();
            
            $original = $model->getOriginal();
            $changes = $model->getChanges();
            
            // Filter out timestamps
            unset($changes['updated_at']);
            
            // If only updated_at changed, ignore (or keep it if important)
            if (empty($changes)) {
                return;
            }

            $properties = [
                'old' => array_intersect_key($original, $changes),
                'new' => $changes,
            ];

            ActivityLog::create([
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'System',
                'action' => 'updated',
                'description' => 'Memperbarui data',
                'subject_type' => get_class($model),
                'subject_id' => $model->id,
                'properties' => $properties,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        // Log Deletion
        static::deleted(function ($model) {
            $user = Auth::user();
            ActivityLog::create([
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'System',
                'action' => 'deleted',
                'description' => 'Menghapus data',
                'subject_type' => get_class($model),
                'subject_id' => $model->id,
                'properties' => ['attributes' => $model->getAttributes()],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
