<?php

namespace App\Http\Controllers;

use App\Models\Stock;

class DashboardController extends Controller
{
    public function index()
    {
        // 日経平均
        $nikkei = Stock::where('ticker', '^N225')
            ->with('latestPrice')
            ->first();

        // ウォッチリスト（指数を除く）
        $stocks = Stock::where('is_active', true)
            ->where('is_index', false)
            ->with(['latestPrice', 'latestFinancial'])
            ->get();

        // 値下がり率ランキング（ウォッチリスト内）
        $declineRanking = $stocks
            ->filter(fn($s) => $s->latestPrice && $s->latestPrice->change_percent !== null)
            ->sortBy(fn($s) => $s->latestPrice->change_percent)
            ->values();

        return view('dashboard', compact('nikkei', 'stocks', 'declineRanking'));
    }
}
