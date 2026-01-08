<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'plan_id',
        'name',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function expenseEntries(): HasMany
    {
        return $this->hasMany(ExpenseEntry::class);
    }

    public function investingEntries(): HasMany
    {
        return $this->hasMany(InvestingEntry::class);
    }

    public function savingGoalEntries(): HasMany
    {
        return $this->hasMany(SavingGoalEntry::class);
    }
}
