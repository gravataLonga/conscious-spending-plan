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
        'total_net_worth',
        'net_income',
        'total_expenses',
        'total_saving',
        'total_investing',
        'guilt_free',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'total_net_worth' => 'decimal:2',
            'net_income' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'total_saving' => 'decimal:2',
            'total_investing' => 'decimal:2',
            'guilt_free' => 'decimal:2',
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
