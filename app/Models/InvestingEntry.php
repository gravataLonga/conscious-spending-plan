<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestingEntry extends Model
{
    protected $fillable = [
        'investing_category_id',
        'partner_id',
        'amount',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InvestingCategory::class, 'investing_category_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
