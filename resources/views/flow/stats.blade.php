@extends('layouts.main')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('stats', () => ({
            flow: @json($flow),
            runs: [],
            stats: {
                totalRuns: 0,
                completedRuns: 0,
                completionRate: 0,
                avgTimeSeconds: 0,
                cardStats: [],
                endCardStats: [],
                dailyRuns: []
            },
            loading: true,
            activeTab: 'overview',

            async init() {
                await this.loadStats();
            },

            async loadStats() {
                this.loading = true;
                try {
                    const response = await fetch(`/api/flow/${this.flow.id}/stats`);
                    const data = await response.json();
                    this.stats = data;
                    this.$nextTick(() => {
                        this.renderCharts();
                    });
                } catch (error) {
                    console.error('Failed to load stats:', error);
                }
                this.loading = false;
            },

            renderCharts() {
                this.renderDailyChart();
                this.renderCardCharts();
            },

            renderDailyChart() {
                const ctx = document.getElementById('dailyChart');
                if (!ctx || !this.stats.dailyRuns) return;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.stats.dailyRuns.map(d => d.date),
                        datasets: [{
                            label: 'Responses',
                            data: this.stats.dailyRuns.map(d => d.count),
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });
            },

            renderCardCharts() {
                this.stats.cardStats.forEach((card, index) => {
                    const ctx = document.getElementById(`cardChart-${index}`);
                    if (!ctx) return;

                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: [card.options[0] || 'Left', card.options[1] || 'Right'],
                            datasets: [{
                                data: [card.leftCount, card.rightCount],
                                backgroundColor: ['#6366f1', '#f59e0b'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '60%',
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                });
            },

            formatTime(seconds) {
                if (seconds === null || seconds === undefined) return '—';
                seconds = Math.abs(seconds);
                if (seconds === 0) return '< 1s';
                if (seconds < 60) return `${Math.round(seconds)}s`;
                const mins = Math.floor(seconds / 60);
                const secs = Math.round(seconds % 60);
                return `${mins}m ${secs}s`;
            },

            formatPercent(value) {
                return Math.round(value || 0) + '%';
            }
        }));
    });
</script>
@endpush

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="stats()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('flow.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm mb-2 inline-block">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back to Svaips
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $flow->name }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $flow->description }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('flow.run', $flow) }}" target="_blank"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                    <i class="fa-solid fa-play mr-2"></i>Run Svaip
                </a>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Stats Content -->
    <div x-show="!loading" x-cloak>
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fa-solid fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Responses</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="stats.totalRuns"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-emerald-100 text-emerald-600">
                        <i class="fa-solid fa-check-circle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Completed</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="stats.completedRuns"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                        <i class="fa-solid fa-percentage text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Completion Rate</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="formatPercent(stats.completionRate)"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fa-solid fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Avg. Time</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="formatTime(stats.avgTimeSeconds)"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Responses Chart -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Responses Over Time</h3>
            <div class="h-64">
                <canvas id="dailyChart"></canvas>
            </div>
            <div x-show="!stats.dailyRuns || stats.dailyRuns.length === 0" class="h-64 flex items-center justify-center text-gray-400">
                <div class="text-center">
                    <i class="fa-solid fa-chart-line text-4xl mb-2"></i>
                    <p>No response data yet</p>
                </div>
            </div>
        </div>

        <!-- Card-by-Card Stats -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Question Breakdown</h3>
            
            <div x-show="stats.cardStats && stats.cardStats.length > 0" class="space-y-6">
                <template x-for="(card, index) in stats.cardStats" :key="index">
                    <div class="border rounded-lg p-4">
                        <div class="flex flex-col md:flex-row md:items-start gap-4">
                            <!-- Question Info -->
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="bg-indigo-100 text-indigo-700 text-xs font-medium px-2 py-1 rounded">
                                        Card #<span x-text="index + 1"></span>
                                    </span>
                                    <span class="text-gray-400 text-sm" x-text="card.totalResponses + ' responses'"></span>
                                </div>
                                <h4 class="font-medium text-gray-900 mb-3" x-text="card.question || 'Untitled question'"></h4>
                                
                                <!-- Answer Bars -->
                                <div class="space-y-2">
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-indigo-600 font-medium" x-text="card.options[0] || 'Left'"></span>
                                            <span class="text-gray-500" x-text="card.leftCount + ' (' + formatPercent(card.leftPercent) + ')'"></span>
                                        </div>
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" 
                                                :style="'width: ' + (card.leftPercent || 0) + '%'"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-amber-600 font-medium" x-text="card.options[1] || 'Right'"></span>
                                            <span class="text-gray-500" x-text="card.rightCount + ' (' + formatPercent(card.rightPercent) + ')'"></span>
                                        </div>
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-amber-500 rounded-full transition-all duration-500" 
                                                :style="'width: ' + (card.rightPercent || 0) + '%'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Doughnut Chart -->
                            <div class="w-32 h-32 flex-shrink-0" x-show="card.totalResponses > 0">
                                <canvas :id="'cardChart-' + index"></canvas>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="!stats.cardStats || stats.cardStats.length === 0" class="py-12 text-center text-gray-400">
                <i class="fa-solid fa-clipboard-question text-4xl mb-2"></i>
                <p>No question data available</p>
            </div>
        </div>

        <!-- End Card Stats -->
        <div x-show="stats.endCardStats && stats.endCardStats.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">End Points Reached</h3>
            <div class="space-y-3">
                <template x-for="(endCard, index) in stats.endCardStats" :key="index">
                    <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-100 rounded-full">
                                <i class="fa-solid fa-flag-checkered text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900" x-text="endCard.message || 'End Card'"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-semibold text-emerald-600" x-text="endCard.count"></p>
                            <p class="text-sm text-gray-500" x-text="formatPercent(endCard.percent) + ' of completions'"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Recent Responses -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Responses</h3>
            <div x-show="stats.recentRuns && stats.recentRuns.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Answers</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="run in stats.recentRuns" :key="run.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="run.date"></td>
                                <td class="px-4 py-3">
                                    <span x-show="run.completed" class="px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-700 rounded-full">
                                        Completed
                                    </span>
                                    <span x-show="!run.completed" class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                                        Incomplete
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500" x-text="formatTime(run.durationSeconds)"></td>
                                <td class="px-4 py-3 text-sm text-gray-500" x-text="run.answerCount + ' answers'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="!stats.recentRuns || stats.recentRuns.length === 0" class="py-12 text-center text-gray-400">
                <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                <p>No responses yet</p>
                <p class="text-sm mt-1">Share your Svaip to start collecting responses</p>
            </div>
        </div>
    </div>
</div>
@endsection
