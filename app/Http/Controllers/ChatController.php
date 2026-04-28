<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        // Ambil 50 pesan terakhir, dibalik agar urutan dari lama ke baru
        $messages = Message::latest()->take(50)->get()->reverse()->values();

        return Inertia::render('Chat', [
            'messages' => $messages,
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'sender'       => 'required|string|max:30',
            'avatar_color' => 'required|string|max:20',
            'body'         => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'sender'       => $request->sender,
            'avatar_color' => $request->avatar_color,
            'body'         => $request->body,
        ]);

        // Broadcast ke semua user yang subscribe channel 'chat'
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => $message,
        ]);
    }
}
