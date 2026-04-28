<?php

namespace App\Http\Controllers;

use App\Events\PrivateMessageSent;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PrivateChatController extends Controller
{
    /**
     * Daftar semua user selain diri sendiri + unread count per user.
     */
    public function users()
    {
        $me = Auth::user();

        $users = User::where('id', '!=', $me->id)
            ->withCount([
                'receivedMessages as unread_count' => function ($query) use ($me) {
                    $query->where('sender_id', $me->id)
                          ->where('is_read', false);
                },
            ])
            ->get()
            ->map(fn ($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'unread_count' => PrivateMessage::where('sender_id', $u->id)
                                    ->where('receiver_id', $me->id)
                                    ->where('is_read', false)
                                    ->count(),
            ]);

        return Inertia::render('PrivateChat/Users', [
            'users' => $users,
        ]);
    }

    /**
     * Buka percakapan dengan user tertentu.
     */
    public function show(User $user)
    {
        $me = Auth::user();

        // Load 50 pesan terakhir antara dua user ini
        $messages = PrivateMessage::with(['sender:id,name', 'receiver:id,name'])
            ->where(function ($q) use ($me, $user) {
                $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($me, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
            })
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        // Tandai semua pesan masuk dari user ini sebagai sudah dibaca
        PrivateMessage::where('sender_id', $user->id)
            ->where('receiver_id', $me->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Channel name (sorted IDs)
        $ids = collect([$me->id, $user->id])->sort()->values();
        $channelName = "chat.{$ids[0]}-{$ids[1]}";

        return Inertia::render('PrivateChat/Chat', [
            'chatWith'    => ['id' => $user->id, 'name' => $user->name],
            'messages'    => $messages,
            'channelName' => $channelName,
        ]);
    }

    /**
     * Kirim pesan private.
     */
    public function send(Request $request, User $user)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $message = PrivateMessage::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $user->id,
            'body'        => $request->body,
        ]);

        $message->load('sender:id,name');

        broadcast(new PrivateMessageSent($message))->toOthers();

        return response()->json(['message' => $message]);
    }
}
