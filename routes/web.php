<?php

use App\Models\Flow;
use App\Models\User;
use App\Models\FlowRun;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\FlowRunController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Resources\FlowRunResource;

Route::middleware('guest')->group(function () {
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/pricing', function () {
        return view('pricing');
    })->name('pricing');
});

Route::get('/flow/{id}/run/{runId}', function ($id, $runId) {
    return view('flow.run.welcome', ['id' => $id, 'runId' => $runId]);
})->middleware('auth')->name('flow.run.welcome');


Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('flow.index', [
            'flows' => auth()->user()->flows,
        ]);
    })->name('flow.index');

    Route::get('/flow/create', function () {
        return view('flow.create');
    })->name('flow.create');

    Route::get('/flow/{id}/edit', function ($id) {
        $flow = Flow::findOrFail($id);
        
        // Ensure user owns the flow
        if ($flow->user_id !== auth()->id()) {
            abort(403);
        }
        
        // Load saved layout positions
        $layout = $flow->layout ?? [];
        
        // Debug logging
        \Log::info('[EDIT] Loading flow', [
            'flow_id' => $flow->id,
            'cards_count' => count($flow->cards ?? []),
            'layout_keys' => array_keys($layout)
        ]);
        
        // Prepare cards data for the UI
        $questionCardsCount = count($flow->cards);
        
        $cardsData = $flow->cards()->map(function($card) use ($flow, $layout) {
            // Find the card index in flow.cards array
            $cardIndex = array_search($card->id, $flow->cards);
            
            \Log::info('[EDIT] Processing card', [
                'card_id' => $card->id,
                'card_index' => $cardIndex
            ]);
            
            // Load connections from database and convert to UI branches format
            $connections = \App\Models\CardConnection::where('source_card_id', $card->id)
                ->orderBy('source_option')
                ->get();
            
            $branches = [null, null];  // Default: no connections (null = go to end card)
            foreach ($connections as $connection) {
                // Find target card's index in the flow
                $targetIndex = array_search($connection->target_card_id, $flow->cards);
                if ($targetIndex !== false) {
                    // Convert to 1-based UI index
                    $branches[$connection->source_option] = $targetIndex + 1;
                }
                // If target not found, leave as null (will point to end card)
            }
            
            \Log::info('[EDIT] Loaded connections', [
                'card_index' => $cardIndex,
                'connections_count' => $connections->count(),
                'branches' => $branches
            ]);
            
            // Get saved position - use card INDEX (0-based) not position
            $position = $layout[$cardIndex] ?? [
                'x' => 100 + ($cardIndex * 50),
                'y' => 100 + ($cardIndex * 30)
            ];
            
            return [
                'id' => $card->id,
                'type' => 'question',
                'question' => $card->question,
                'description' => $card->description ?? '',
                'options' => $card->options,
                'branches' => $branches,
                'scoring' => $card->scoring,
                'skipable' => $card->skipable ?? false,
                'x' => (int)$position['x'],  // Cast to int
                'y' => (int)$position['y'],  // Cast to int
            ];
        })->values()->toArray();
        
        // Load end cards from metadata and append to cards array
        $metadata = $flow->metadata ?? [];
        $endCardUiIndex = null;
        
        if (isset($metadata['end_cards']) && is_array($metadata['end_cards'])) {
            \Log::info('[EDIT] Loading end cards', ['count' => count($metadata['end_cards'])]);
            
            foreach ($metadata['end_cards'] as $index => $endCard) {
                $endPosition = $layout["end_{$index}"] ?? [
                    'x' => 100 + (count($cardsData) * 50),
                    'y' => 100 + (count($cardsData) * 30)
                ];
                
                // The end card's UI index is the count of cards so far (0-based) + 1 for 1-based
                $endCardUiIndex = count($cardsData) + 1;
                
                $cardsData[] = [
                    'type' => 'end',
                    'message' => $endCard['message'] ?? 'Thank you for completing!',
                    'formFields' => $endCard['formFields'] ?? [],
                    'x' => (int)$endPosition['x'],
                    'y' => (int)$endPosition['y'],
                ];
                
                \Log::info('[EDIT] Added end card', ['index' => $index, 'ui_index' => $endCardUiIndex, 'position' => $endPosition]);
            }
        }
        
        // Now convert null branches to point to end card if it exists
        if ($endCardUiIndex !== null) {
            foreach ($cardsData as &$card) {
                if ($card['type'] === 'question') {
                    for ($i = 0; $i < count($card['branches']); $i++) {
                        // If branch is null and we have an end card, point to it
                        // BUT: we need to distinguish between "explicitly no connection" vs "should go to end card"
                        // For now, keep null branches as null (they mean "end flow, show end card automatically")
                        // This is the correct behavior
                    }
                }
            }
        }
        
        \Log::info('[EDIT] Final cards data', ['total_cards' => count($cardsData), 'end_card_index' => $endCardUiIndex]);
        
        return view('flow.edit', [
            'flow' => $flow,
            'cardsData' => $cardsData,
        ]);
    })->name('flow.edit');

    Route::get('/flow/{id}/settings', function ($id) {
        $flow = Flow::findOrFail($id);
        
        // Ensure user owns the flow
        if ($flow->user_id !== auth()->id()) {
            abort(403);
        }
        
        return view('flow.settings', ['flow' => $flow]);
    })->name('flow.settings');

    Route::get('/flow/{id}/analytics', [AnalyticsController::class, 'show'])->name('flow.analytics');
    Route::get('/flow/{id}/analytics/data', [AnalyticsController::class, 'data'])->name('flow.analytics.data');
    
    Route::get('/flow/{flow}/responses', [ResponseController::class, 'index'])->name('flow.responses');
    Route::get('/flow/{flow}/responses/{run}', [ResponseController::class, 'show'])->name('flow.response.detail');

    Route::post('/flow/{id}/toggle-public', [FlowController::class, 'togglePublic'])->name('flow.toggle-public');

    Route::post('/flow/store', [FlowController::class, 'store'])->name('flow.store');
    Route::post('/flow/{id}/update', [FlowController::class, 'update'])->name('flow.update');

    Route::get('/card', [CardController::class, 'index']);
    Route::post('/card', [CardController::class, 'store']);

    Route::get('/flow', [FlowController::class, 'index']);
    Route::post('/flow', [FlowController::class, 'store']);
    Route::get('/flow/{id}', [FlowController::class, 'show']);
    
    // Redirect authenticated users to public flow run
    Route::get('/flow/{flow}/run', function(Flow $flow) {
        // If public, redirect to public URL
        if ($flow->is_public && $flow->public_slug) {
            return redirect()->route('public.flow.show', $flow->public_slug);
        }
        
        // Otherwise show a message or create a private run
        return redirect()->route('flow.index')->with('error', 'Flow must be public to run');
    })->name('flow.run');

    Route::get('/run/{id}/start', function($id) {
        $run = FlowRun::findOrFail($id);
        // check if the run is already started/stopped.
        $run->started();
        $resource = new FlowRunResource($run);
        return view('flow.run.show', ['flow' => $resource]);
    })->name('flow-run-start');

    Route::post('/flow/{id}/run', [FlowRunController::class, 'create']);
    Route::post('/flow/{id}/run/{flowRunId}/stop', [FlowRunController::class, 'stop']);
    Route::get('/flow/{id}/run/{flowRunId}/start', [FlowRunController::class, 'start']);
});

// Public flow routes (anonymous access)
Route::prefix('p')->name('public.flow.')->group(function () {
    Route::get('/{slug}', [App\Http\Controllers\PublicFlowController::class, 'show'])->name('show');
    Route::post('/{slug}/start', [App\Http\Controllers\PublicFlowController::class, 'start'])->name('start');
    Route::get('/{slug}/run/{runId}', [App\Http\Controllers\PublicFlowController::class, 'run'])->name('run');
    Route::post('/{slug}/run/{runId}/answer', [App\Http\Controllers\PublicFlowController::class, 'answer'])->name('answer');
    Route::get('/{slug}/run/{runId}/result', [App\Http\Controllers\PublicFlowController::class, 'result'])->name('result');
});

// Public routes for sharing results
Route::get('/results/{shareToken}', function($shareToken) {
    $flowRun = FlowRun::where('share_token', $shareToken)->firstOrFail();
    
    // Make sure the flow run is completed
    if (!$flowRun->completed_at) {
        abort(404, 'This result is not yet available.');
    }
    
    return view('results.show', [
        'flowRun' => $flowRun,
        'resultTemplate' => $flowRun->resultTemplate,
        'flow' => $flowRun->flow,
    ]);
})->name('results.show');
