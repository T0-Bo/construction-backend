<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function logUpdate(
        Model $model,
        array $oldValues,
        array $newValues
    ): AuditLog {
        return AuditLog::create([
            'company_id' => $model->company_id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
