<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class YahooFinanceService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; KabuApp/1.0)',
            ],
        ]);
    }

    /**
     * 株価・チャートデータを取得（日足・週足・月足）
     */
    public function getChartData(string $ticker, string $range = '1y', string $interval = '1d'): ?array
    {
        try {
            $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}";
            $response = $this->client->get($url, [
                'query' => [
                    'range'    => $range,
                    'interval' => $interval,
                    'events'   => 'history',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['chart']['result'][0] ?? null;
        } catch (RequestException $e) {
            Log::error("YahooFinance チャート取得失敗 [{$ticker}]: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 株価サマリー（現在値・前日比・52週高安値など）を取得
     */
    public function getQuoteSummary(string $ticker): ?array
    {
        try {
            $url = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$ticker}";
            $response = $this->client->get($url, [
                'query' => [
                    'modules' => 'price,summaryDetail,defaultKeyStatistics,financialData',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['quoteSummary']['result'][0] ?? null;
        } catch (RequestException $e) {
            Log::error("YahooFinance サマリー取得失敗 [{$ticker}]: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ニュースを取得
     */
    public function getNews(string $ticker): array
    {
        try {
            $url = "https://query1.finance.yahoo.com/v1/finance/search";
            $response = $this->client->get($url, [
                'query' => [
                    'q'           => $ticker,
                    'newsCount'   => 10,
                    'quotesCount' => 0,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['news'] ?? [];
        } catch (RequestException $e) {
            Log::error("YahooFinance ニュース取得失敗 [{$ticker}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 移動平均を計算（終値の配列から）
     */
    public function calculateMA(array $closes, int $period): ?float
    {
        if (count($closes) < $period) {
            return null;
        }
        $slice = array_slice($closes, -$period);
        return round(array_sum($slice) / $period, 2);
    }
}
