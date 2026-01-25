<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $flow->name }} - SVAIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Geist', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-b from-slate-100 via-gray-100 to-slate-100 bg-no-repeat bg-fixed min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white shadow-lg rounded-2xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $flow->name }}</h1>
            @if($flow->description)
                <p class="text-lg text-gray-500 mb-6">{{ $flow->description }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('public.flow.start', $flow->public_slug) }}" class="text-center">
            @csrf
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-8 py-3 rounded-lg text-lg transition-colors">
                Start Flow
            </button>
        </form>

        <div class="mt-8 text-center text-sm text-gray-400">
            <p>Powered by SVAIP</p>
        </div>
    </div>
</body>
</html>
