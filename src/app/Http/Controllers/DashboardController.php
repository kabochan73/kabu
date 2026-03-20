<?php

namespace App\Http\Controllers;

use App\Models\Stock;

class DashboardController extends Controller
{
    public function index()
    {
        $stocks = Stock::where('is_active', true)
            ->with(['latestPrice', 'latestFinancial'])
            ->get();

        // 値下がり率ランキング（ウォッチリスト内）
        $declineRanking = $stocks
            ->filter(fn($s) => $s->latestPrice && $s->latestPrice->change_percent !== null)
            ->sortBy(fn($s) => $s->latestPrice->change_percent)
            ->values();

        return view('dashboard', compact('stocks', 'declineRanking'));
    }
}
