<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\IssueController;
use App\Http\Controllers\ArPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.login'));

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [IssueController::class, 'index'])->name('issues.index');
        Route::get('/issues/create', [IssueController::class, 'create'])->name('issues.create');
        Route::post('/issues', [IssueController::class, 'store'])->name('issues.store');
        Route::get('/issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
        Route::delete('/issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');
        Route::post('/issues/{issue}/images', [IssueController::class, 'uploadImages'])->name('issues.images.upload');
        Route::delete('/issues/{issue}/images/{image}', [IssueController::class, 'deleteImage'])->name('issues.images.delete');
        Route::post('/issues/{issue}/images/{image}/video', [IssueController::class, 'saveVideo'])->name('issues.videos.save');
        Route::post('/issues/{issue}/compile', [IssueController::class, 'compile'])->name('issues.compile');
        Route::get('/issues/{issue}/qr.png', [IssueController::class, 'qr'])->name('issues.qr');
    });
});

Route::get('/ar/{code}', [ArPageController::class, 'show'])->name('ar.page');
