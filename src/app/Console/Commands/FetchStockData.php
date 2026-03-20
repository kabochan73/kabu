<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\StockDataService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('stock:fetch {--ticker= : 特定の銘柄コードのみ取得}')]
#[Description('ウォッチリストの株価情報をYahoo Financeから取得して保存する')]
class FetchStockData extends Command
{
    public function handle(StockDataService $service): int
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

        $this->info("取得開始: {$stocks->count()}銘柄");
        $bar = $this->output->createProgressBar($stocks->count());
        $bar->start();

        foreach ($stocks as $stock) {
            $service->fetchAndSave($stock);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('全銘柄の取得が完了しました。');

        return self::SUCCESS;
    }
}
