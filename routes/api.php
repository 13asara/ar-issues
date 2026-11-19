<?php

use App\Http\Controllers\Api\CompileCallbackController;
use App\Http\Controllers\Api\IssueConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/issues/{code}', [IssueConfigController::class, 'show'])->name('api.issues.show');
});

Route::post('/compile/callback', [CompileCallbackController::class, 'store'])->name('api.compile.callback');
