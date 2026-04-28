<?php

namespace App\Events;

use App\Models\PrivateMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PrivateMessage $message) {}

    public function broadcastOn(): array
    {
        // Sorted IDs agar kedua user pakai channel yang sama
        $ids = collect([$this->message->sender_id, $this->message->receiver_id])->sort()->values();

        return [
            // Channel untuk pesan (hanya dua user yang terlibat)
            new PrivateChannel("chat.{$ids[0]}-{$ids[1]}"),
            // Channel notifikasi khusus untuk receiver
            new PrivateChannel("notifications.{$this->message->receiver_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'private.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->message->id,
            'sender_id'   => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'sender_name' => $this->message->sender->name,
            'body'        => $this->message->body,
            'time'        => $this->message->time,
        ];
    }
}
