<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/download', [DownloadController::class, 'index'])->name('download');

// Step 1: Generate download ID → frontend subscribe ke channel
Route::post('/download/prepare', [DownloadController::class, 'prepare'])->name('download.prepare');

// Step 2: Frontend sudah listen → baru dispatch job
Route::post('/download/dispatch', [DownloadController::class, 'dispatch'])->name('download.dispatch');

// Serve file hasil generate
Route::get('/download/file/{fileName}', [DownloadController::class, 'download'])->name('download.file');

// Chat routes
Route::get('/chat', [ChatController::class, 'index'])->name('chat');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
