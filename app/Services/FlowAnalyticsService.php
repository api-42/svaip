<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\FlowRun;
use App\Models\FlowRunResult;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FlowAnalyticsService
{
    protected Flow $flow;
    protected ?Carbon $startDate = null;
    protected ?Carbon $endDate = null;
    protected ?string $completionStatus = null;
    protected ?int $resultTemplateId = null;

    public function __construct(Flow $flow)
    {
        $this->flow = $flow;
    }

    public function setDateRange(?string $startDate, ?string $endDate): self
    {
        $this->startDate = $startDate ? Carbon::parse($startDate) : null;
        $this->endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        return $this;
    }

    public function setCompletionStatus(?string $status): self
    {
        $this->completionStatus = $status; // 'completed', 'abandoned', 'all'
        return $this;
    }

    public function setResultTemplate(?int $resultTemplateId): self
    {
        $this->resultTemplateId = $resultTemplateId;
        return $this;
    }

    public function getOverviewMetrics(): array
    {
        $query = $this->getBaseQuery();

        $totalRuns = (clone $query)->count();
        $completedRuns = (clone $query)->whereNotNull('completed_at')->count();
        $completionRate = $totalRuns > 0 ? round(($completedRuns / $totalRuns) * 100, 1) : 0;

        $averageScore = (clone $query)
            ->whereNotNull('completed_at')
            ->avg('total_score') ?? 0;

        // Calculate average completion time (compatible with SQLite and MySQL)
        $completedRunsForTime = (clone $query)
            ->whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->get(['started_at', 'completed_at']);

        $totalSeconds = 0;
        $count = 0;
        foreach ($completedRunsForTime as $run) {
            $totalSeconds += Carbon::parse($run->completed_at)->diffInSeconds(Carbon::parse($run->started_at));
            $count++;
        }
        $averageCompletionTime = $count > 0 ? $totalSeconds / $count : 0;

        // Unique visitors - count both authenticated and anonymous users
        $authenticatedVisitors = (clone $query)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $anonymousVisitors = (clone $query)
            ->whereNull('user_id')
            ->whereNotNull('session_token')
            ->distinct('session_token')
            ->count('session_token');

        $uniqueVisitors = $authenticatedVisitors + $anonymousVisitors;

        return [
            'total_runs' => $totalRuns,
            'completed_runs' => $completedRuns,
            'abandoned_runs' => $totalRuns - $completedRuns,
            'completion_rate' => $completionRate,
            'average_score' => round($averageScore, 2),
            'average_completion_time_seconds' => round($averageCompletionTime),
            'average_completion_time_formatted' => $this->formatDuration($averageCompletionTime),
            'unique_visitors' => $uniqueVisitors,
        ];
    }

    public function getScoreDistribution(): array
    {
        $query = $this->getBaseQuery()
            ->whereNotNull('completed_at')
            ->whereNotNull('total_score');

        $scores = (clone $query)->pluck('total_score');

        if ($scores->isEmpty()) {
            return [
                'histogram' => [],
                'min' => 0,
                'max' => 0,
                'median' => 0,
                'result_template_distribution' => [],
            ];
        }

        // Create histogram with 10 buckets
        $min = $scores->min();
        $max = $scores->max();
        $bucketSize = $max > $min ? ($max - $min) / 10 : 1;

        $histogram = [];
        for ($i = 0; $i < 10; $i++) {
            $rangeStart = $min + ($i * $bucketSize);
            $rangeEnd = $i === 9 ? $max : $rangeStart + $bucketSize;
            $count = $scores->filter(function ($score) use ($rangeStart, $rangeEnd) {
                return $score >= $rangeStart && $score <= $rangeEnd;
            })->count();

            $histogram[] = [
                'range' => round($rangeStart, 1) . '-' . round($rangeEnd, 1),
                'count' => $count,
            ];
        }

        // Result template distribution - use JOIN for proper eager loading
        $resultTemplateDistribution = (clone $query)
            ->join('result_templates', 'flow_runs.result_template_id', '=', 'result_templates.id')
            ->select(
                'flow_runs.result_template_id',
                'result_templates.title as template_title',
                DB::raw('count(*) as count')
            )
            ->groupBy('flow_runs.result_template_id', 'result_templates.title')
            ->get()
            ->map(function ($item) {
                return [
                    'template_id' => $item->result_template_id,
                    'template_title' => $item->template_title,
                    'count' => $item->count,
                ];
            });

        return [
            'histogram' => $histogram,
            'min' => round($min, 2),
            'max' => round($max, 2),
            'median' => round($scores->median(), 2),
            'result_template_distribution' => $resultTemplateDistribution,
        ];
    }

    public function getTimeBasedTrends(): array
    {
        $query = $this->getBaseQuery()->whereNotNull('completed_at');

        $completionsByDate = (clone $query)
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            });

        $scoresByDate = (clone $query)
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('AVG(total_score) as avg_score'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'average_score' => round($item->avg_score, 2),
                ];
            });

        return [
            'completions_by_date' => $completionsByDate,
            'scores_by_date' => $scoresByDate,
        ];
    }

    public function getPerCardAnalytics(): array
    {
        $cards = $this->flow->cards();
        $flowRunIds = $this->getBaseQuery()->pluck('id')->toArray();

        $analytics = [];

        foreach ($cards as $card) {
            // Response distribution
            $responseDistribution = FlowRunResult::whereIn('flow_run_id', $flowRunIds)
                ->where('card_id', $card->id)
                ->select('answer', DB::raw('count(*) as count'))
                ->groupBy('answer')
                ->get()
                ->keyBy('answer');

            // Drop-off rate (users who this was their last card)
            $lastCardCount = FlowRun::whereIn('id', $flowRunIds)
                ->whereNull('completed_at')
                ->whereHas('results', function ($q) use ($card) {
                    $q->where('card_id', $card->id);
                })
                ->whereDoesntHave('results', function ($q) use ($card) {
                    $q->where('card_id', '>', $card->id);
                })
                ->count();

            $totalAnswered = FlowRunResult::whereIn('flow_run_id', $flowRunIds)
                ->where('card_id', $card->id)
                ->count();

            $analytics[] = [
                'card_id' => $card->id,
                'card_question' => $card->question,
                'total_answered' => $totalAnswered,
                'answer_0_count' => $responseDistribution->get(0)?->count ?? 0,
                'answer_1_count' => $responseDistribution->get(1)?->count ?? 0,
                'drop_off_count' => $lastCardCount,
                'drop_off_rate' => $totalAnswered > 0 ? round(($lastCardCount / $totalAnswered) * 100, 1) : 0,
            ];
        }

        return $analytics;
    }

    protected function getBaseQuery()
    {
        $query = FlowRun::where('flow_id', $this->flow->id);

        if ($this->startDate) {
            $query->where('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('created_at', '<=', $this->endDate);
        }

        if ($this->completionStatus === 'completed') {
            $query->whereNotNull('completed_at');
        } elseif ($this->completionStatus === 'abandoned') {
            $query->whereNull('completed_at');
        }

        if ($this->resultTemplateId) {
            $query->where('result_template_id', $this->resultTemplateId);
        }

        return $query;
    }

    protected function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . 'm ' . round($seconds % 60) . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }
}
