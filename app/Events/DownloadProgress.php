<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DownloadProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $downloadId,
        public int $progress,
        public string $status,
        public string $message = '',
        public ?string $fileName = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('download.' . $this->downloadId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'progress.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'download_id' => $this->downloadId,
            'progress'    => $this->progress,
            'status'      => $this->status,
            'message'     => $this->message,
            'file_name'   => $this->fileName,
        ];
    }
}
