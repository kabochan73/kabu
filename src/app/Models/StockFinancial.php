<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockFinancial extends Model
{
    protected $fillable = [
        'stock_id', 'fiscal_year', 'revenue', 'operating_income', 'net_income',
        'eps', 'per', 'pbr', 'dividend_yield', 'market_cap', 'fetched_at',
    ];

    protected $casts = ['fetched_at' => 'date'];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
