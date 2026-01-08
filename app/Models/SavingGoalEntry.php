<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingGoalEntry extends Model
{
    protected $fillable = [
        'saving_goal_category_id',
        'partner_id',
        'amount',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SavingGoalCategory::class, 'saving_goal_category_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
