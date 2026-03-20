<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\StockPrice;
use App\Services\YahooFinanceService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('stock:fetch-history {--ticker= : 特定の銘柄コードのみ}')]
#[Description('ウォッチリストの過去1年分の株価データを取得して保存する')]
class FetchStockHistory extends Command
{
    public function handle(YahooFinanceService $yahoo): int
    {
        $ticker = $this->option('ticker');

        $query = Stock::where('is_active', true);
        if ($ticker) {
            $query->where('ticker', $ticker);
        }

        $stocks = $query->get();

        if ($stocks->isEmpty()) {
            $this->warn('ウォッチリストに銘柄がありません。');
            return self::FAILURE;
        }

        $this->info("過去1年分の株価取得開始: {$stocks->count()}銘柄");

        foreach ($stocks as $stock) {
            $this->info("  取得中: {$stock->name} ({$stock->ticker})");

            $chart = $yahoo->getChartData($stock->ticker, '1y', '1d');
            if (!$chart) {
                $this->warn("  → 取得失敗: {$stock->ticker}");
                continue;
            }

            $timestamps = $chart['timestamp'] ?? [];
            $quotes = $chart['indicators']['quote'][0] ?? [];
            $closes  = $quotes['close']  ?? [];
            $opens   = $quotes['open']   ?? [];
            $highs   = $quotes['high']   ?? [];
            $lows    = $quotes['low']    ?? [];
            $volumes = $quotes['volume'] ?? [];

            // 移動平均計算用に終値の累積配列を持つ
            $closeSoFar = [];
            $savedCount = 0;

            foreach ($timestamps as $i => $ts) {
                $close = $closes[$i] ?? null;
                if ($close === null) continue;

                $closeSoFar[] = $close;
                $date = Carbon::createFromTimestamp($ts)->toDateString();

                StockPrice::updateOrCreate(
                    ['stock_id' => $stock->id, 'date' => $date],
                    [
                        'close'  => $close,
                        'open'   => $opens[$i]   ?? null,
                        'high'   => $highs[$i]   ?? null,
                        'low'    => $lows[$i]    ?? null,
                        'volume' => $volumes[$i] ?? null,
                        'ma5'    => $yahoo->calculateMA($closeSoFar, 5),
                        'ma25'   => $yahoo->calculateMA($closeSoFar, 25),
                        'ma75'   => $yahoo->calculateMA($closeSoFar, 75),
                    ]
                );

                $savedCount++;
            }

            $this->info("  → {$savedCount}日分 保存完了");
        }

        $this->info('全銘柄の過去データ取得が完了しました。');
        return self::SUCCESS;
    }
}
