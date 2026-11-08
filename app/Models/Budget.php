<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'project_id',
        'amount',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->whereRelation(
            'project',
            'company_id',
            $companyId
        );
    }
}
