<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function show(Request $request, Stock $stock)
    {
        $stock->load([
            'latestPrice',
            'latestFinancial',
            'news' => fn($q) => $q->orderByDesc('published_at')->limit(10),
        ]);

        // 期間パラメータ（デフォルト3ヶ月）
        $period = $request->query('period', '3m');
        $periods = [
            '1m' => ['label' => '1ヶ月', 'days' => 30],
            '3m' => ['label' => '3ヶ月', 'days' => 90],
            '6m' => ['label' => '6ヶ月', 'days' => 180],
            '1y' => ['label' => '1年',   'days' => 365],
        ];
        $days = $periods[$period]['days'] ?? 90;
        $since = Carbon::today()->subDays($days)->toDateString();

        $chartData = $stock->prices()
            ->where('date', '>=', $since)
            ->orderBy('date')
            ->get(['date', 'open', 'high', 'low', 'close', 'volume', 'ma5', 'ma25', 'ma75'])
            ->map(fn($p) => [
                'date'   => $p->date->toDateString(),
                'open'   => $p->open,
                'high'   => $p->high,
                'low'    => $p->low,
                'close'  => $p->close,
                'volume' => $p->volume,
                'ma5'    => $p->ma5,
                'ma25'   => $p->ma25,
                'ma75'   => $p->ma75,
            ]);

        return view('stocks.show', compact('stock', 'chartData', 'period', 'periods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ticker' => 'required|string|unique:stocks,ticker',
            'name'   => 'required|string|max:100',
        ]);

        Stock::create([
            'ticker' => strtoupper($request->ticker),
            'name'   => $request->name,
        ]);

        return redirect()->route('dashboard')->with('success', "{$request->name} をウォッチリストに追加しました。");
    }

    public function destroy(Stock $stock)
    {
        $stock->update(['is_active' => false]);
        return redirect()->route('dashboard')->with('success', "{$stock->name} をウォッチリストから削除しました。");
    }
}
