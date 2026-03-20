<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockNews extends Model
{
    protected $fillable = ['stock_id', 'title', 'url', 'source', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
