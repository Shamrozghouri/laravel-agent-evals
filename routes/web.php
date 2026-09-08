<?php

use Ali\LaravelAgentEvals\Http\Controllers\AgentEvalsDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AgentEvalsDashboardController::class, 'index'])
    ->name('agent-evals.dashboard');

Route::post('/run', [AgentEvalsDashboardController::class, 'run'])
    ->name('agent-evals.dashboard.run');
