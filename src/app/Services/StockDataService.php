<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockFinancial;
use App\Models\StockNews;
use App\Models\StockPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StockDataService
{
    public function __construct(private YahooFinanceService $yahoo) {}

    public function fetchAndSave(Stock $stock): void
    {
        Log::info("取得開始: {$stock->name} ({$stock->ticker})");

        $this->savePrice($stock);
        $this->saveFinancials($stock);
        $this->saveNews($stock);
    }

    private function savePrice(Stock $stock): void
    {
        // 1年分の日足データを取得（移動平均計算のため）
        $chart = $this->yahoo->getChartData($stock->ticker, '1y', '1d');
        $summary = $this->yahoo->getQuoteSummary($stock->ticker);

        if (!$chart || !$summary) {
            Log::warning("株価データ取得失敗: {$stock->ticker}");
            return;
        }

        $timestamps = $chart['timestamp'] ?? [];
        $closes = $chart['indicators']['quote'][0]['close'] ?? [];
        $opens  = $chart['indicators']['quote'][0]['open'] ?? [];
        $highs  = $chart['indicators']['quote'][0]['high'] ?? [];
        $lows   = $chart['indicators']['quote'][0]['low'] ?? [];
        $volumes = $chart['indicators']['quote'][0]['volume'] ?? [];

        $price = $summary['price'] ?? [];
        $summaryDetail = $summary['summaryDetail'] ?? [];

        // 今日分の株価を保存
        $today = Carbon::today()->toDateString();
        $closesFiltered = array_filter($closes, fn($v) => $v !== null);

        StockPrice::updateOrCreate(
            ['stock_id' => $stock->id, 'date' => $today],
            [
                'close'          => $price['regularMarketPrice']['raw'] ?? end($closesFiltered) ?: null,
                'open'           => $price['regularMarketOpen']['raw'] ?? null,
                'high'           => $price['regularMarketDayHigh']['raw'] ?? null,
                'low'            => $price['regularMarketDayLow']['raw'] ?? null,
                'volume'         => $price['regularMarketVolume']['raw'] ?? null,
                'previous_close' => $price['regularMarketPreviousClose']['raw'] ?? null,
                'change'         => $price['regularMarketChange']['raw'] ?? null,
                'change_percent' => $price['regularMarketChangePercent']['raw']
                    ? round($price['regularMarketChangePercent']['raw'] * 100, 2)
                    : null,
                'week52_high'    => $summaryDetail['fiftyTwoWeekHigh']['raw'] ?? null,
                'week52_low'     => $summaryDetail['fiftyTwoWeekLow']['raw'] ?? null,
                'ma5'            => $this->yahoo->calculateMA(array_values($closesFiltered), 5),
                'ma25'           => $this->yahoo->calculateMA(array_values($closesFiltered), 25),
                'ma75'           => $this->yahoo->calculateMA(array_values($closesFiltered), 75),
            ]
        );

        Log::info("株価保存完了: {$stock->ticker}");
    }

    private function saveFinancials(Stock $stock): void
    {
        $summary = $this->yahoo->getQuoteSummary($stock->ticker);
        if (!$summary) return;

        $price   = $summary['price'] ?? [];
        $keyStats = $summary['defaultKeyStatistics'] ?? [];
        $financial = $summary['financialData'] ?? [];

        StockFinancial::updateOrCreate(
            ['stock_id' => $stock->id, 'fetched_at' => Carbon::today()->toDateString()],
            [
                'revenue'          => $financial['totalRevenue']['raw'] ?? null,
                'operating_income' => $financial['operatingCashflow']['raw'] ?? null,
                'net_income'       => $financial['netIncomeToCommon']['raw'] ?? null,
                'eps'              => $keyStats['trailingEps']['raw'] ?? null,
                'per'              => $price['priceToBook']['raw'] ?? null,
                'pbr'              => $keyStats['priceToBook']['raw'] ?? null,
                'dividend_yield'   => isset($summary['summaryDetail']['dividendYield']['raw'])
                    ? round($summary['summaryDetail']['dividendYield']['raw'] * 100, 2)
                    : null,
                'market_cap'       => $price['marketCap']['raw'] ?? null,
            ]
        );

        Log::info("財務情報保存完了: {$stock->ticker}");
    }

    private function saveNews(Stock $stock): void
    {
        $newsList = $this->yahoo->getNews($stock->ticker);

        foreach ($newsList as $item) {
            StockNews::updateOrInsert(
                ['stock_id' => $stock->id, 'url' => $item['url'] ?? ''],
                [
                    'title'        => $item['title'] ?? '',
                    'source'       => $item['source'] ?? 'Yahoo!ファイナンス',
                    'published_at' => $item['published_at'] ?? null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );
        }

        Log::info("ニュース保存完了: {$stock->ticker} (" . count($newsList) . "件)");
    }
}
