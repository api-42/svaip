<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\FlowRunController;
use App\Http\Controllers\Api\ResultTemplateController;

Route::middleware('auth')->group(function () {
    Route::get('/card', [CardController::class, 'index']);
    Route::post('/card', [CardController::class, 'store']);

    Route::get('/flow', [FlowController::class, 'index']);
    Route::post('/flow', [FlowController::class, 'store']);
    Route::get('/flow/{id}', [FlowController::class, 'show']);
    Route::post('/flow/{id}/toggle-public', [FlowController::class, 'togglePublic']);

    Route::get('/flow/{flowId}/result-templates', [ResultTemplateController::class, 'index']);
    Route::post('/flow/{flowId}/result-templates', [ResultTemplateController::class, 'store']);
    Route::put('/flow/{flowId}/result-templates/{templateId}', [ResultTemplateController::class, 'update']);
    Route::delete('/flow/{flowId}/result-templates/{templateId}', [ResultTemplateController::class, 'destroy']);

    Route::post('/flow/{id}/run', [FlowRunController::class, 'create']);
    Route::post('/flow/{id}/run/{flowRunId}/stop', [FlowRunController::class, 'stop']);

    Route::post('/run/{id}/stop', [FlowRunController::class, 'stop']);
    Route::post('/run/{id}/answer', [FlowRunController::class, 'answer']);
Route::post('/flow/{id}/run/{flowRunId}/answer', [FlowRunController::class, 'answer']);
});
