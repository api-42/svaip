<?php

use App\Models\FlowRun;
use Illuminate\Support\Facades\Route;

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/register', function () {
    request()->validate([
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
    ]);

    $user = \App\Models\User::create([
        'name' => 'web user',
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

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('flow.create');
    });

    Route::post('/flow/create', function () {
        $flowRun = Flow::create([
            'user_id' => auth()->id(),
            'status' => 'created',
        ]);

        return redirect()->route('flow.run.welcome', ['id' => $flowRun->id, 'runId' => $flowRun->id]);
    })->name('flow.store');
});

Route::get('/flow/{id}/run/{runId}', function ($id, $runId) {
    return view('flow.run.welcome', ['id' => $id, 'runId' => $runId]);
})->name('flow.run.welcome');
