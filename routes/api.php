<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\FlowRunController;
use App\Http\Controllers\Api\ResultTemplateController;
use App\Http\Controllers\Api\TokenController;

// Auth routes - need 'web' middleware for session support
Route::middleware(['web'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Token generation for SPA (authenticated users only)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/token', [TokenController::class, 'generateToken']);
    Route::post('/auth/revoke', [TokenController::class, 'revokeToken']);
});

// API Routes (Session-based auth for web SPA)
Route::middleware(['web', 'auth'])->group(function () {
    // Flow CRUD
    Route::apiResource('flows', FlowController::class);
    Route::post('flows/{id}/toggle-public', [FlowController::class, 'togglePublic']);
    
    // Cards
    Route::get('/cards', [CardController::class, 'index']);
    Route::post('/cards', [CardController::class, 'store']);
    
    // Result Templates
    Route::get('/flows/{flowId}/result-templates', [ResultTemplateController::class, 'index']);
    Route::post('/flows/{flowId}/result-templates', [ResultTemplateController::class, 'store']);
    Route::put('/flows/{flowId}/result-templates/{templateId}', [ResultTemplateController::class, 'update']);
    Route::delete('/flows/{flowId}/result-templates/{templateId}', [ResultTemplateController::class, 'destroy']);
    
    // Flow Runs
    Route::post('/flows/{id}/run', [FlowRunController::class, 'create']);
    Route::post('/flows/{id}/run/{flowRunId}/stop', [FlowRunController::class, 'stop']);
    Route::post('/flows/{id}/run/{flowRunId}/answer', [FlowRunController::class, 'answer']);
    Route::post('/runs/{id}/stop', [FlowRunController::class, 'stop']);
    Route::post('/runs/{id}/answer', [FlowRunController::class, 'answer']);
});

