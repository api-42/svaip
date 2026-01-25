<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - {{ $flow->name }}</title>
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
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-lg p-8">
        <!-- Completion message -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">You have reached the Svaips end.</h1>
            <p class="text-gray-500">You've finished {{ $flow->name }}</p>
        </div>

        <!-- Score display -->
        @if($run->total_score !== null)
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6 mb-8 text-center">
                <p class="text-sm text-emerald-600 font-semibold uppercase mb-1">Your Score</p>
                <p class="text-4xl font-bold text-emerald-900">{{ $run->total_score }}</p>
            </div>
        @endif

        <!-- Result template content -->
        @if($run->resultTemplate)
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ $run->resultTemplate->title }}</h2>
                <div class="text-gray-600 prose max-w-none">
                    {!! nl2br(e($run->resultTemplate->content)) !!}
                </div>
                
                @if($run->resultTemplate->cta_text && $run->resultTemplate->cta_url)
                    <div class="mt-6">
                        <a href="{{ $run->resultTemplate->cta_url }}" 
                           target="_blank"
                           class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                            {{ $run->resultTemplate->cta_text }}
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Share link -->
        <div class="border-t pt-6">
            <p class="text-sm text-gray-500 mb-2">Share your results:</p>
            <div class="flex gap-2">
                <input type="text" 
                       value="{{ route('results.show', $run->share_token) }}" 
                       readonly 
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50"
                       onclick="this.select()">
                <button onclick="copyShareLink()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-semibold transition-colors">
                    Copy
                </button>
            </div>
        </div>

        <div class="mt-8 text-center text-sm text-gray-400">
            <p>Powered by SVAIP</p>
        </div>
    </div>

    <script>
        function copyShareLink() {
            const input = document.querySelector('input[readonly]');
            input.select();
            document.execCommand('copy');
            
            const button = document.querySelector('button[onclick="copyShareLink()"]');
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            setTimeout(() => {
                button.textContent = originalText;
            }, 2000);
        }
    </script>
</body>
</html>
