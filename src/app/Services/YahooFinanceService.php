<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

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
     * Yahoo!ファイナンス Japan から日本語ニュースを取得
     * ticker例: 7203.T → コード部分 7203 を使う
     */
    public function getNews(string $ticker): array
    {
        // "7203.T" → "7203"
        $code = explode('.', $ticker)[0];

        try {
            $response = $this->client->get("https://finance.yahoo.co.jp/quote/{$code}/news", [
                'headers' => [
                    'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept-Language' => 'ja,en;q=0.9',
                ],
            ]);

            $html    = $response->getBody()->getContents();
            $crawler = new Crawler($html);
            $news    = [];

            // timeタグを持つli要素のみニュースとして取得
            $crawler->filter('li')->each(function (Crawler $node) use (&$news) {
                $linkNode = $node->filter('a');
                $timeNode = $node->filter('time');

                // 日時がないものはニュースではない（メニューなど）
                if ($linkNode->count() === 0 || $timeNode->count() === 0) return;

                $title = trim($linkNode->text(''));
                $href  = $linkNode->attr('href') ?? '';

                if (empty($title) || empty($href)) return;

                // 相対URLを絶対URLに変換
                if (!str_starts_with($href, 'http')) {
                    $href = 'https://finance.yahoo.co.jp' . $href;
                }

                $datetime    = $timeNode->attr('datetime');
                $publishedAt = $datetime ? \Carbon\Carbon::parse($datetime) : null;

                $news[] = [
                    'title'        => $title,
                    'url'          => $href,
                    'source'       => 'Yahoo!ファイナンス',
                    'published_at' => $publishedAt,
                ];
            });

            return array_slice($news, 0, 15);
        } catch (RequestException $e) {
            Log::error("Yahoo!ファイナンス Japan ニュース取得失敗 [{$ticker}]: " . $e->getMessage());
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
