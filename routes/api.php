<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\FlowRunController;

Route::get('/card', [CardController::class, 'index']);
Route::post('/card', [CardController::class, 'store']);

Route::get('/flow', [FlowController::class, 'index']);
Route::post('/flow', [FlowController::class, 'store']);
Route::get('/flow/{id}', [FlowController::class, 'show']);

Route::post('/flow/{id}/run', [FlowRunController::class, 'create']);
Route::post('/flow/{id}/run/{flowRunId}/stop', [FlowRunController::class, 'stop']);
Route::get('/flow/{id}/run/{flowRunId}/start', [FlowRunController::class, 'start']);
Route::post('/flow/{id}/run/{flowRunId}/answer', [FlowRunController::class, 'answer']);
