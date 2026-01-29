<?php

namespace App\Http\Controllers;

use App\Models\Flow;
use App\Models\FlowRun;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    /**
     * Display a listing of responses for a flow
     */
    public function index(Flow $flow)
    {
        $this->authorize('view', $flow);
        
        $query = $flow->runs()
            ->with(['user', 'resultTemplate'])
            ->withCount('formResponses');
        
        // Apply filters
        if (request('status') === 'completed') {
            $query->whereNotNull('completed_at');
        } elseif (request('status') === 'incomplete') {
            $query->whereNull('completed_at');
        }
        
        if (request('date_from')) {
            $query->where('started_at', '>=', request('date_from'));
        }
        
        if (request('date_to')) {
            $query->where('started_at', '<=', request('date_to') . ' 23:59:59');
        }
        
        $runs = $query->latest('started_at')->paginate(20);
        
        // Summary stats
        $totalRuns = $flow->runs()->count();
        $completedRuns = $flow->runs()->whereNotNull('completed_at')->count();
        $completionRate = $totalRuns > 0 ? round(($completedRuns / $totalRuns) * 100) : 0;
        
        // Calculate average duration using Laravel collections (SQLite compatible)
        $completedRunsWithDuration = $flow->runs()
            ->whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->get(['started_at', 'completed_at']);
        
        $avgDuration = null;
        if ($completedRunsWithDuration->count() > 0) {
            $totalSeconds = $completedRunsWithDuration->sum(function ($run) {
                return $run->started_at->diffInSeconds($run->completed_at);
            });
            $avgDuration = $totalSeconds / $completedRunsWithDuration->count();
        }
        
        return view('flow.responses', compact('flow', 'runs', 'totalRuns', 'completedRuns', 'completionRate', 'avgDuration'));
    }
    
    /**
     * Display a specific response detail
     */
    public function show(Flow $flow, FlowRun $run)
    {
        $this->authorize('view', $flow);
        
        if ($run->flow_id !== $flow->id) {
            abort(404, 'Run does not belong to this flow');
        }
        
        $run->load(['results.card', 'formResponses', 'resultTemplate', 'user']);
        $timings = $run->getCardTimings();
        
        return view('flow.response-detail', compact('flow', 'run', 'timings'));
    }
}
