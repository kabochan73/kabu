@extends('layouts.app')

@section('title', '株価ダッシュボード')

@section('content')
{{-- 日経平均 --}}
@if($nikkei)
    @php $p = $nikkei->latestPrice; @endphp
    <div class="bg-white rounded-lg shadow p-6 mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('stocks.show', $nikkei) }}" class="text-sm text-gray-500 hover:text-blue-600 hover:underline mb-1 inline-block">日経平均株価</a>
            <div class="text-3xl font-bold font-mono">
                {{ $p ? number_format($p->close, 2) : '-' }} 円
            </div>
        </div>
        @if($p)
            <div class="text-right">
                <div class="text-xl font-mono {{ $p->change >= 0 ? 'text-red-600' : 'text-blue-600' }}">
                    {{ $p->change >= 0 ? '+' : '' }}{{ number_format($p->change, 2) }} 円
                </div>
                <div class="text-lg font-mono {{ $p->change_percent >= 0 ? 'text-red-600' : 'text-blue-600' }}">
                    {{ $p->change_percent >= 0 ? '+' : '' }}{{ $p->change_percent }}%
                </div>
                <div class="text-xs text-gray-400 mt-1">{{ $p->date->format('Y年m月d日') }} 時点</div>
            </div>
        @else
            <div class="text-gray-400 text-sm">データ未取得</div>
        @endif
    </div>
@endif

{{-- 銘柄追加フォーム --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">銘柄を追加</h2>
    <form action="{{ route('stocks.store') }}" method="POST" class="flex gap-3">
        @csrf
        <input type="text" name="ticker" placeholder="銘柄コード（例: 7203.T）"
            class="border rounded px-3 py-2 w-48 text-sm" required>
        <input type="text" name="name" placeholder="銘柄名（例: トヨタ自動車）"
            class="border rounded px-3 py-2 w-56 text-sm" required>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
            追加
        </button>
    </form>
    @error('ticker') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
</div>

{{-- ウォッチリスト --}}
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold">ウォッチリスト</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">銘柄</th>
                    <th class="px-4 py-3 text-right">現在値</th>
                    <th class="px-4 py-3 text-right">前日比</th>
                    <th class="px-4 py-3 text-right">前日比(%)</th>
                    <th class="px-4 py-3 text-right">52週高値</th>
                    <th class="px-4 py-3 text-right">52週安値</th>
                    <th class="px-4 py-3 text-right">出来高</th>
                    <th class="px-4 py-3 text-center">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($stocks as $stock)
                    @php $p = $stock->latestPrice; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('stocks.show', $stock) }}" class="font-medium text-blue-600 hover:underline">
                                {{ $stock->name }}
                            </a>
                            <span class="text-gray-400 text-xs ml-1">{{ $stock->ticker }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            {{ $p ? number_format($p->close, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono {{ $p && $p->change >= 0 ? 'text-red-600' : 'text-blue-600' }}">
                            {{ $p ? ($p->change >= 0 ? '+' : '') . number_format($p->change, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono {{ $p && $p->change_percent >= 0 ? 'text-red-600' : 'text-blue-600' }}">
                            {{ $p ? ($p->change_percent >= 0 ? '+' : '') . $p->change_percent . '%' : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-gray-600">
                            {{ $p ? number_format($p->week52_high, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-gray-600">
                            {{ $p ? number_format($p->week52_low, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600">
                            {{ $p ? number_format($p->volume) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form action="{{ route('stocks.destroy', $stock) }}" method="POST"
                                onsubmit="return confirm('削除しますか？')">
                                @csrf @method('DELETE')
                                <button class="text-red-400 hover:text-red-600 text-xs">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                            銘柄が登録されていません。上のフォームから追加してください。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 値下がり率ランキング --}}
@if($declineRanking->isNotEmpty())
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold">値下がり率ランキング（ウォッチリスト内）</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">順位</th>
                    <th class="px-4 py-3 text-left">銘柄</th>
                    <th class="px-4 py-3 text-right">現在値</th>
                    <th class="px-4 py-3 text-right">前日比(%)</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($declineRanking as $i => $stock)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('stocks.show', $stock) }}" class="font-medium text-blue-600 hover:underline">
                                {{ $stock->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            {{ number_format($stock->latestPrice->close, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-blue-600 font-semibold">
                            {{ $stock->latestPrice->change_percent }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
