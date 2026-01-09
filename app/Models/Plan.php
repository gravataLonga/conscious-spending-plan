<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'currency',
        'buffer_percent',
        'user_id',
        'is_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'is_snapshot' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    public function netWorths(): HasMany
    {
        return $this->hasMany(NetWorth::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class)->orderBy('sort');
    }

    public function investingCategories(): HasMany
    {
        return $this->hasMany(InvestingCategory::class)->orderBy('sort');
    }

    public function savingGoalCategories(): HasMany
    {
        return $this->hasMany(SavingGoalCategory::class)->orderBy('sort');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PlanSnapshot::class)->latest('captured_at');
    }
}
