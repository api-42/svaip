<?php

namespace App\Http\Controllers\Api;

use App\Models\Flow;
use App\Models\Card;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FlowStatsController extends Controller
{
    public function show($id)
    {
        $flow = Flow::findOrFail($id);
        
        // Ensure user owns this flow
        if ($flow->user_id !== auth()->id()) {
            abort(403);
        }

        $runs = $flow->runs()->with('results')->get();
        
        // Basic stats
        $totalRuns = $runs->count();
        $completedRuns = $runs->whereNotNull('completed_at')->count();
        $completionRate = $totalRuns > 0 ? ($completedRuns / $totalRuns) * 100 : 0;
        
        // Average completion time
        $avgTimeSeconds = 0;
        $completedWithTime = $runs->filter(function ($run) {
            return $run->started_at && $run->completed_at;
        });
        
        if ($completedWithTime->count() > 0) {
            $totalSeconds = $completedWithTime->sum(function ($run) {
                $start = Carbon::parse($run->started_at);
                $end = Carbon::parse($run->completed_at);
                return abs($end->diffInSeconds($start));
            });
            $avgTimeSeconds = $totalSeconds / $completedWithTime->count();
        }

        // Daily runs (last 30 days)
        $dailyRuns = $flow->runs()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'count' => $item->count
                ];
            });

        // Card-by-card stats
        $cardStats = [];
        $cards = is_array($flow->cards) ? $flow->cards : [];
        
        foreach ($cards as $index => $cardData) {
            // Skip end cards for question stats
            if (isset($cardData['type']) && $cardData['type'] === 'end') {
                continue;
            }

            // Get all results for this card position
            $cardResults = DB::table('flow_run_results')
                ->join('flow_runs', 'flow_run_results.flow_run_id', '=', 'flow_runs.id')
                ->where('flow_runs.flow_id', $flow->id)
                ->where('flow_run_results.card_id', $index)
                ->whereNotNull('flow_run_results.answer')
                ->select('flow_run_results.answer')
                ->get();

            $leftCount = $cardResults->where('answer', 0)->count();
            $rightCount = $cardResults->where('answer', 1)->count();
            $total = $leftCount + $rightCount;

            // Get options - ensure array values are properly indexed
            $options = isset($cardData['options']) && is_array($cardData['options']) 
                ? array_values($cardData['options']) 
                : ['Left', 'Right'];

            $cardStats[] = [
                'question' => $cardData['question'] ?? '',
                'options' => $options,
                'leftCount' => $leftCount,
                'rightCount' => $rightCount,
                'totalResponses' => $total,
                'leftPercent' => $total > 0 ? ($leftCount / $total) * 100 : 0,
                'rightPercent' => $total > 0 ? ($rightCount / $total) * 100 : 0,
            ];
        }

        // End card stats
        $endCardStats = [];
        foreach ($cards as $index => $cardData) {
            if (isset($cardData['type']) && $cardData['type'] === 'end') {
                // Count how many runs ended at this card
                // For now, we'll count completed runs that have this as their last answered card
                $endCardStats[] = [
                    'message' => $cardData['message'] ?? 'End Card',
                    'count' => 0, // TODO: Track which end card was reached
                    'percent' => 0,
                ];
            }
        }

        // Recent runs
        $recentRuns = $flow->runs()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($run) {
                $duration = null;
                if ($run->started_at && $run->completed_at) {
                    $start = Carbon::parse($run->started_at);
                    $end = Carbon::parse($run->completed_at);
                    $duration = abs($end->diffInSeconds($start));
                }

                return [
                    'id' => $run->id,
                    'date' => Carbon::parse($run->created_at)->format('M d, Y H:i'),
                    'completed' => !is_null($run->completed_at),
                    'durationSeconds' => $duration,
                    'answerCount' => $run->results()->whereNotNull('answer')->count(),
                ];
            });

        return response()->json([
            'totalRuns' => $totalRuns,
            'completedRuns' => $completedRuns,
            'completionRate' => $completionRate,
            'avgTimeSeconds' => $avgTimeSeconds,
            'dailyRuns' => $dailyRuns,
            'cardStats' => $cardStats,
            'endCardStats' => $endCardStats,
            'recentRuns' => $recentRuns,
        ]);
    }
}
