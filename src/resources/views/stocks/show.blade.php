@extends('layouts.app')

@section('title', $stock->name . ' - 株価詳細')

@section('content')
<div class="mb-4">
    <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline text-sm">← ダッシュボードに戻る</a>
</div>

{{-- ヘッダー --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold">{{ $stock->name }}</h1>
            <span class="text-gray-500 text-sm">{{ $stock->ticker }} / {{ $stock->market }}</span>
        </div>
        @if($stock->latestPrice)
            @php $p = $stock->latestPrice; @endphp
            <div class="text-right">
                <div class="text-3xl font-bold font-mono">{{ number_format($p->close, 2) }} 円</div>
                <div class="text-lg font-mono {{ $p->change >= 0 ? 'text-red-600' : 'text-blue-600' }}">
                    {{ $p->change >= 0 ? '+' : '' }}{{ number_format($p->change, 2) }}
                    （{{ $p->change_percent >= 0 ? '+' : '' }}{{ $p->change_percent }}%）
                </div>
                <div class="text-xs text-gray-400 mt-1">{{ $p->date->format('Y年m月d日') }} 時点</div>
            </div>
        @endif
    </div>
</div>

{{-- 株価指標 --}}
@if($stock->latestPrice)
    @php $p = $stock->latestPrice; @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([
            ['始値', number_format($p->open, 2) . ' 円'],
            ['高値', number_format($p->high, 2) . ' 円'],
            ['安値', number_format($p->low, 2) . ' 円'],
            ['出来高', number_format($p->volume)],
            ['52週高値', number_format($p->week52_high, 2) . ' 円'],
            ['52週安値', number_format($p->week52_low, 2) . ' 円'],
            ['MA5', $p->ma5 ? number_format($p->ma5, 2) . ' 円' : '-'],
            ['MA25', $p->ma25 ? number_format($p->ma25, 2) . ' 円' : '-'],
        ] as [$label, $value])
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 mb-1">{{ $label }}</div>
                <div class="font-semibold font-mono">{{ $value }}</div>
            </div>
        @endforeach
    </div>
@endif

{{-- チャート --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">株価チャート（終値・移動平均線）</h2>
        <div class="flex gap-2">
            @foreach($periods as $key => $info)
                <a href="{{ route('stocks.show', [$stock, 'period' => $key]) }}"
                    class="px-3 py-1 rounded text-sm font-medium
                        {{ $period === $key
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $info['label'] }}
                </a>
            @endforeach
        </div>
    </div>
    <canvas id="priceChart" height="100"></canvas>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- 財務情報 --}}
    @if($stock->latestFinancial)
        @php $f = $stock->latestFinancial; @endphp
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">財務情報</h2>
            <dl class="space-y-3 text-sm">
                @foreach([
                    ['売上高', $f->revenue ? number_format($f->revenue) . ' 円' : '-'],
                    ['営業利益', $f->operating_income ? number_format($f->operating_income) . ' 円' : '-'],
                    ['純利益', $f->net_income ? number_format($f->net_income) . ' 円' : '-'],
                    ['EPS', $f->eps ? number_format($f->eps, 2) . ' 円' : '-'],
                    ['PER', $f->per ? $f->per . ' 倍' : '-'],
                    ['PBR', $f->pbr ? $f->pbr . ' 倍' : '-'],
                    ['配当利回り', $f->dividend_yield ? $f->dividend_yield . '%' : '-'],
                    ['時価総額', $f->market_cap ? number_format($f->market_cap) . ' 円' : '-'],
                ] as [$label, $value])
                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="font-medium">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    {{-- ニュース --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">最新ニュース</h2>
        @forelse($stock->news as $news)
            <div class="mb-4 pb-4 border-b last:border-0">
                <a href="{{ $news->url }}" target="_blank"
                    class="text-sm font-medium text-blue-600 hover:underline">
                    {{ $news->title }}
                </a>
                <div class="text-xs text-gray-400 mt-1">
                    {{ $news->source }} ・
                    {{ $news->published_at ? $news->published_at->format('Y/m/d H:i') : '-' }}
                </div>
            </div>
        @empty
            <p class="text-gray-400 text-sm">ニュースがありません</p>
        @endforelse
    </div>
</div>

<script>
const chartData = @json($chartData);
const labels = chartData.map(d => d.date);

new Chart(document.getElementById('priceChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: '終値',
                data: chartData.map(d => d.close),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                borderWidth: 2,
                pointRadius: 0,
                fill: true,
            },
            {
                label: 'MA5',
                data: chartData.map(d => d.ma5),
                borderColor: '#f59e0b',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
            },
            {
                label: 'MA25',
                data: chartData.map(d => d.ma25),
                borderColor: '#10b981',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
            },
            {
                label: 'MA75',
                data: chartData.map(d => d.ma75),
                borderColor: '#ef4444',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
            },
        ],
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' } },
        scales: {
            x: { ticks: { maxTicksLimit: 10 } },
            y: { ticks: { callback: v => v.toLocaleString() + '円' } },
        },
    },
});
</script>
@endsection
