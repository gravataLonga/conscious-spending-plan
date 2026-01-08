<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingGoalCategory extends Model
{
    protected $fillable = [
        'plan_id',
        'name',
        'sort',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SavingGoalEntry::class);
    }
}
