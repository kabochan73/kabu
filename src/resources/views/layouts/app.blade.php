<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '株価ダッシュボード')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 text-gray-800">
    <nav class="bg-white shadow px-6 py-4 flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-blue-600">株価ダッシュボード</a>
        <span class="text-sm text-gray-500">{{ now()->format('Y年m月d日') }}</span>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
