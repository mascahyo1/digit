<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\PrivateChatController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Public ────────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// ── Auth (hanya untuk guest / belum login) ────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Download (public, tidak perlu login) ─────────────────────────────────────
Route::get('/download', [DownloadController::class, 'index'])->name('download');
Route::post('/download/prepare', [DownloadController::class, 'prepare'])->name('download.prepare');
Route::post('/download/dispatch', [DownloadController::class, 'dispatch'])->name('download.dispatch');
Route::get('/download/status/{downloadId}', [DownloadController::class, 'status'])->name('download.status'); // polling fallback
Route::get('/download/file/{fileName}', [DownloadController::class, 'download'])->name('download.file');

// ── Chat & Private Chat (harus login) ────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Public chat (tetap ada, sekarang perlu login)
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/heartbeat', [ChatController::class, 'heartbeat'])->name('chat.heartbeat');

    // Private chat
    Route::get('/private-chat', [PrivateChatController::class, 'users'])->name('private-chat.users');
    Route::get('/private-chat/{user}', [PrivateChatController::class, 'show'])->name('private-chat.show');
    Route::post('/private-chat/{user}/send', [PrivateChatController::class, 'send'])->name('private-chat.send');
    Route::get('/private-chat/{user}/messages', [PrivateChatController::class, 'messagesSince'])->name('private-chat.messages-since');
});
