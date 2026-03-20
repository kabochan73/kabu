<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockPrice extends Model
{
    protected $fillable = [
        'stock_id', 'date', 'close', 'open', 'high', 'low', 'volume',
        'previous_close', 'change', 'change_percent',
        'week52_high', 'week52_low', 'ma5', 'ma25', 'ma75',
    ];

    protected $casts = ['date' => 'date'];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
