@extends('layouts.main')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('flow.responses', $flow) }}" class="text-indigo-600 hover:text-indigo-800 text-sm mb-2 inline-block">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back to Responses
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Response Detail</h1>
        <p class="text-gray-600 mt-1">{{ $flow->name }}</p>
    </div>

    <!-- Run Metadata -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Run Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <div class="text-sm text-gray-600">Run ID</div>
                <div class="text-sm font-mono text-gray-900">{{ Str::limit($run->id, 12) }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">User</div>
                <div class="text-sm text-gray-900">
                    @if($run->user)
                        {{ $run->user->name }}
                        <div class="text-xs text-gray-500">{{ $run->user->email }}</div>
                    @else
                        <span class="italic">Anonymous</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-600">Started At</div>
                <div class="text-sm text-gray-900">{{ $run->started_at->format('M d, Y H:i:s') }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">Status</div>
                <div class="text-sm">
                    @if($run->completed_at)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Completed
                        </span>
                        <div class="text-xs text-gray-500 mt-1">{{ $run->completed_at->format('M d, Y H:i:s') }}</div>
                    @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Incomplete
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        @if($run->completed_at)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-200">
                <div>
                    <div class="text-sm text-gray-600">Total Duration</div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ gmdate('i:s', $run->started_at->diffInSeconds($run->completed_at)) }}
                    </div>
                </div>
                @if($run->total_score !== null)
                    <div>
                        <div class="text-sm text-gray-600">Score</div>
                        <div class="text-lg font-semibold text-indigo-600">{{ $run->total_score }} points</div>
                    </div>
                @endif
                @if($run->resultTemplate)
                    <div>
                        <div class="text-sm text-gray-600">Result Template</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $run->resultTemplate->name }}</div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Card-by-Card Answers -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Card-by-Card Progression</h2>
        </div>
        
        @if($run->results->where('answer', '!==', null)->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Answer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Spent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php
                            $cardNumber = 1;
                        @endphp
                        @foreach($run->results->sortBy('answered_at') as $result)
                            @if($result->answer !== null && $result->card)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $cardNumber++ }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ $result->card->question }}</div>
                                        @if($result->card->description)
                                            <div class="text-xs text-gray-500 mt-1">{{ $result->card->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @php
                                            $answerText = $result->card->options[$result->answer] ?? ($result->answer == 0 ? 'Left' : 'Right');
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $result->answer == 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $answerText }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if(isset($timings[$result->card_id]))
                                            <span class="font-mono">{{ number_format($timings[$result->card_id], 1) }}s</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($result->answered_at)
                                            {{ $result->answered_at->format('H:i:s') }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-8 text-center text-gray-500">
                <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-2"></i>
                <p>No answers recorded for this run.</p>
            </div>
        @endif
    </div>

    <!-- End Card Form Responses -->
    @if($run->formResponses->count() > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fa-solid fa-clipboard-list mr-2 text-emerald-600"></i>
                End Card Form Data
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($run->formResponses as $response)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-600 mb-1">{{ $response->field_name }}</div>
                        <div class="text-sm text-gray-900">
                            @if(strlen($response->field_value) > 100)
                                <div class="max-h-32 overflow-y-auto">{{ $response->field_value }}</div>
                            @else
                                {{ $response->field_value ?: '(empty)' }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
