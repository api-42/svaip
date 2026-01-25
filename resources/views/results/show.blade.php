@extends('layouts.main')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 to-blue-50 py-12 px-4">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $flow->name }}</h1>
            <p class="text-gray-600">Your Results</p>
        </div>

        <!-- Score Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-6">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full mb-4">
                    <span class="text-4xl font-bold text-white">{{ $flowRun->total_score }}</span>
                </div>
                <p class="text-gray-600">Total Score</p>
            </div>

            @if($resultTemplate)
                <!-- Result Template -->
                <div class="border-t pt-8">
                    @if($resultTemplate->image_url)
                        <div class="mb-6 rounded-lg overflow-hidden">
                            <img src="{{ $resultTemplate->image_url }}" alt="{{ $resultTemplate->title }}" class="w-full h-64 object-cover">
                        </div>
                    @endif

                    <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $resultTemplate->title }}</h2>
                    
                    <div class="prose prose-lg max-w-none text-gray-700 mb-6">
                        {!! nl2br(e($resultTemplate->content)) !!}
                    </div>

                    @if($resultTemplate->cta_text && $resultTemplate->cta_url)
                        <div class="text-center mt-8">
                            <a href="{{ $resultTemplate->cta_url }}" 
                               class="inline-block bg-gradient-to-r from-purple-600 to-blue-600 text-white font-semibold px-8 py-3 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all transform hover:scale-105">
                                {{ $resultTemplate->cta_text }}
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <!-- No specific result template -->
                <div class="text-center text-gray-600">
                    <p>Thank you for completing this flow!</p>
                </div>
            @endif
        </div>

        <!-- Share Section -->
        <div class="bg-white rounded-2xl shadow-xl p-6 text-center">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Share Your Results</h3>
            <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 mb-4">
                <input 
                    type="text" 
                    readonly 
                    value="{{ route('results.show', $flowRun->share_token) }}" 
                    id="shareUrl"
                    class="flex-1 bg-transparent text-gray-700 text-sm outline-none"
                >
                <button 
                    onclick="copyShareUrl()"
                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                    Copy Link
                </button>
            </div>
            <p class="text-xs text-gray-500">Share this link to show others your results</p>
        </div>

        <!-- Completion Info -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Completed on {{ $flowRun->completed_at->format('F j, Y') }} at {{ $flowRun->completed_at->format('g:i A') }}</p>
        </div>
    </div>
</div>

<script>
function copyShareUrl() {
    const input = document.getElementById('shareUrl');
    input.select();
    document.execCommand('copy');
    
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'Copied!';
    setTimeout(() => {
        button.textContent = originalText;
    }, 2000);
}
</script>
@endsection
