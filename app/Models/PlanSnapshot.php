<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanSnapshot extends Model
{
    protected $fillable = [
        'plan_id',
        'snapshot_plan_id',
        'name',
        'captured_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function snapshotPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'snapshot_plan_id');
    }
}
