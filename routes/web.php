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
