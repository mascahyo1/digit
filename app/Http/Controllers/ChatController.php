<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $messages = Message::latest()->take(50)->get()->reverse()->values();

        return Inertia::render('Chat', [
            'messages' => $messages,
        ]);
    }

    public function send(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        // Warna avatar deterministik berdasarkan user ID — konsisten tiap login
        $colors       = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f97316', '#6366f1'];
        $avatarColor  = $colors[$user->id % count($colors)];

        $message = Message::create([
            'sender'       => $user->name,
            'avatar_color' => $avatarColor,
            'body'         => $request->body,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => $message]);
    }

    /**
     * Heartbeat — frontend ping setiap 30 detik saat halaman chat terbuka.
     * Dipakai untuk menentukan apakah user sedang online.
     * TTL 70 detik (sedikit lebih dari interval 30 detik + toleransi).
     */
    public function heartbeat(Request $request)
    {
        $userId = Auth::id();
        Cache::put("user_online_{$userId}", true, now()->addSeconds(70));

        // Ambil daftar semua user yang sedang online
        $onlineUsers = User::all()
            ->filter(fn($u) => Cache::has("user_online_{$u->id}"))
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'online_users' => $onlineUsers
        ]);
    }
}
