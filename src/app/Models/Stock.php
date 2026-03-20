<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stock extends Model
{
    protected $fillable = ['ticker', 'name', 'market', 'is_active', 'is_index'];

    protected $casts = ['is_active' => 'boolean', 'is_index' => 'boolean'];

    public function prices(): HasMany
    {
        return $this->hasMany(StockPrice::class);
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(StockPrice::class)->latestOfMany('date');
    }

    public function financials(): HasMany
    {
        return $this->hasMany(StockFinancial::class);
    }

    public function latestFinancial(): HasOne
    {
        return $this->hasOne(StockFinancial::class)->latestOfMany('fetched_at');
    }

    public function news(): HasMany
    {
        return $this->hasMany(StockNews::class);
    }
}
