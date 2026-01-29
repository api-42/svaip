@extends('layouts.main')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="analyticsApp()">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('flow.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm mb-2 inline-block">
                ← Back to Flows
            </a>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Analytics</h1>
                    <p class="text-gray-600 mt-2">{{ $flow->name }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('flow.settings', $flow->id) }}" 
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md font-medium transition-colors">
                        Settings
                    </a>
                    <button @click="refreshData()" 
                            :disabled="loading"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium transition-colors disabled:opacity-50">
                        <span x-show="!loading">🔄 Refresh</span>
                        <span x-show="loading">Loading...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Filters</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" 
                           x-model="filters.start_date"
                           @change="refreshData()"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" 
                           x-model="filters.end_date"
                           @change="refreshData()"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Completion Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select x-model="filters.status"
                            @change="refreshData()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="all">All</option>
                        <option value="completed">Completed Only</option>
                        <option value="abandoned">Abandoned Only</option>
                    </select>
                </div>

                <!-- Result Template -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Result Type</label>
                    <select x-model="filters.result_template_id"
                            @change="refreshData()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Results</option>
                        @foreach($resultTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-600">Loading analytics...</p>
        </div>

        <!-- Analytics Content -->
        <div x-show="!loading && data" x-cloak>
            <!-- Overview Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Total Runs</h3>
                    <p class="text-3xl font-bold text-gray-900" x-text="data.overview.total_runs"></p>
                </div>
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Completions</h3>
                    <p class="text-3xl font-bold text-emerald-600" x-text="data.overview.completed_runs"></p>
                    <p class="text-sm text-gray-500 mt-1" x-text="data.overview.completion_rate + '% completion rate'"></p>
                </div>
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Average Score</h3>
                    <p class="text-3xl font-bold text-indigo-600" x-text="data.overview.average_score"></p>
                </div>
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Avg Completion Time</h3>
                    <p class="text-3xl font-bold text-gray-900" x-text="data.overview.average_completion_time_formatted"></p>
                </div>
            </div>

            <!-- Score Distribution -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Score Distribution</h2>
                <template x-if="data.score_distribution.histogram.length > 0">
                    <div>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Min</p>
                                <p class="text-2xl font-bold" x-text="data.score_distribution.min"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Median</p>
                                <p class="text-2xl font-bold" x-text="data.score_distribution.median"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Max</p>
                                <p class="text-2xl font-bold" x-text="data.score_distribution.max"></p>
                            </div>
                        </div>
                        
                        <!-- Simple Bar Chart -->
                        <div class="space-y-2">
                            <template x-for="bucket in data.score_distribution.histogram" :key="bucket.range">
                                <div class="flex items-center gap-4">
                                    <div class="w-24 text-sm text-gray-600" x-text="bucket.range"></div>
                                    <div class="flex-1 bg-gray-200 rounded-full h-6 relative">
                                        <div class="bg-indigo-600 h-6 rounded-full transition-all duration-500"
                                             :style="`width: ${(bucket.count / Math.max(...data.score_distribution.histogram.map(b => b.count))) * 100}%`">
                                        </div>
                                        <span class="absolute inset-0 flex items-center justify-center text-xs font-medium"
                                              x-text="bucket.count"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Result Template Distribution -->
                        <div class="mt-6" x-show="data.score_distribution.result_template_distribution.length > 0">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Result Types</h3>
                            <div class="space-y-2">
                                <template x-for="result in data.score_distribution.result_template_distribution" :key="result.template_id">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <span class="font-medium" x-text="result.template_title"></span>
                                        <span class="text-gray-600" x-text="result.count + ' users'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="data.score_distribution.histogram.length === 0">
                    <p class="text-gray-500 text-center py-8">No completed runs yet</p>
                </template>
            </div>

            <!-- Time-Based Trends -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Completions Over Time</h2>
                <template x-if="data.trends.completions_by_date.length > 0">
                    <div class="space-y-2">
                        <template x-for="day in data.trends.completions_by_date" :key="day.date">
                            <div class="flex items-center gap-4">
                                <div class="w-32 text-sm text-gray-600" x-text="day.date"></div>
                                <div class="flex-1 bg-gray-200 rounded-full h-6 relative">
                                    <div class="bg-emerald-600 h-6 rounded-full transition-all duration-500"
                                         :style="`width: ${(day.count / Math.max(...data.trends.completions_by_date.map(d => d.count))) * 100}%`">
                                    </div>
                                    <span class="absolute inset-0 flex items-center justify-center text-xs font-medium"
                                          x-text="day.count"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="data.trends.completions_by_date.length === 0">
                    <p class="text-gray-500 text-center py-8">No completions yet</p>
                </template>
            </div>

            <!-- Per-Card Analytics -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Per-Card Analytics</h2>
                <template x-if="data.per_card.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Answered</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Option 1</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Option 2</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Drop-off Rate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="card in data.per_card" :key="card.card_id">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="card.card_question"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center" x-text="card.total_answered"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center" x-text="card.answer_0_count"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center" x-text="card.answer_1_count"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                  :class="card.drop_off_rate > 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                                  x-text="card.drop_off_rate + '%'">
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
                <template x-if="data.per_card.length === 0">
                    <p class="text-gray-500 text-center py-8">No cards found</p>
                </template>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && !data" class="text-center py-12">
            <p class="text-gray-600">No data available. Try adjusting your filters.</p>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script>
        function analyticsApp() {
            return {
                loading: true,
                data: null,
                filters: {
                    start_date: '',
                    end_date: '',
                    status: 'all',
                    result_template_id: ''
                },

                async init() {
                    await this.refreshData();
                },

                async refreshData() {
                    this.loading = true;
                    
                    const params = new URLSearchParams();
                    if (this.filters.start_date) params.append('start_date', this.filters.start_date);
                    if (this.filters.end_date) params.append('end_date', this.filters.end_date);
                    if (this.filters.status) params.append('status', this.filters.status);
                    if (this.filters.result_template_id) params.append('result_template_id', this.filters.result_template_id);

                    try {
                        const response = await fetch(`{{ route('flow.analytics.data', $flow->id) }}?${params}`);
                        this.data = await response.json();
                    } catch (error) {
                        console.error('Failed to fetch analytics:', error);
                        alert('Failed to load analytics data');
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
@endsection
