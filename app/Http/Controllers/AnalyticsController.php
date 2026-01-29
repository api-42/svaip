<?php

namespace App\Http\Controllers;

use App\Models\Flow;
use App\Services\FlowAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnalyticsController extends Controller
{
    public function show($id)
    {
        $flow = Flow::with('resultTemplates')->findOrFail($id);
        $this->ensureOwnsFlow($flow);

        return view('flow.analytics', [
            'flow' => $flow,
            'resultTemplates' => $flow->resultTemplates,
        ]);
    }

    public function data(Request $request, $id)
    {
        $flow = Flow::findOrFail($id);
        $this->ensureOwnsFlow($flow);

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:all,completed,abandoned',
            'result_template_id' => 'nullable|exists:result_templates,id',
        ]);

        $analytics = new FlowAnalyticsService($flow);
        
        $analytics->setDateRange(
            $request->input('start_date'),
            $request->input('end_date')
        );

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $analytics->setCompletionStatus($request->input('status'));
        }

        if ($request->filled('result_template_id')) {
            $analytics->setResultTemplate($request->input('result_template_id'));
        }

        return response()->json([
            'overview' => $analytics->getOverviewMetrics(),
            'score_distribution' => $analytics->getScoreDistribution(),
            'trends' => $analytics->getTimeBasedTrends(),
            'per_card' => $analytics->getPerCardAnalytics(),
        ]);
    }

    private function ensureOwnsFlow(Flow $flow): void
    {
        if ($flow->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN, 'Not authorized to access this flow.');
        }
    }
}
