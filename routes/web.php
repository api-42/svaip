<?php

use App\Models\Flow;
use App\Models\User;
use App\Models\FlowRun;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\FlowRunController;
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

Route::post('/register', function () {
    request()->validate([
        'password' => 'required|min:8',
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
    ]);

    $user = User::create([
        'name' => request('name'),
        'email' => request('email'),
        'password' => bcrypt(request('password')),
    ]);

    auth()->login($user);

    return redirect('/');
});

Route::post('/login', function () {
    request()->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (auth()->attempt(request()->only('email', 'password'))) {
        return redirect()->intended('/');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->withInput(request()->only('email'));
});


Route::get('/flow/{id}/run/{runId}', function ($id, $runId) {
    return view('flow.run.welcome', ['id' => $id, 'runId' => $runId]);
})->middleware('auth')->name('flow.run.welcome');


Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        auth()->logout();

        return redirect('/login');
    })->name('logout');

    Route::get('/', function () {
        return view('flow.index', [
            'flows' => auth()->user()->flows,
        ]);
    })->name('flow.index');

    Route::get('/flow/create', function () {
        return view('flow.create');
    })->name('flow.create');

    Route::get('/flow/{id}/settings', function ($id) {
        $flow = Flow::findOrFail($id);
        
        // Ensure user owns the flow
        if ($flow->user_id !== auth()->id()) {
            abort(403);
        }
        
        return view('flow.settings', ['flow' => $flow]);
    })->name('flow.settings');

    Route::post('/flow/{id}/toggle-public', [FlowController::class, 'togglePublic'])->name('flow.toggle-public');

    Route::post('/flow/store', [FlowController::class, 'store'])->name('flow.store');

    Route::get('/card', [CardController::class, 'index']);
    Route::post('/card', [CardController::class, 'store']);

    Route::get('/flow', [FlowController::class, 'index']);
    Route::post('/flow', [FlowController::class, 'store']);
    Route::get('/flow/{id}', [FlowController::class, 'show']);
    Route::get('/flow/{id}/run', [FlowController::class, 'run'])->name('flow.run');

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
