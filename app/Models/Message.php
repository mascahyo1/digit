<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'sender',
        'avatar_color',
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Format waktu untuk ditampilkan di frontend
    public function getTimeAttribute(): string
    {
        return $this->created_at->format('H:i');
    }

    protected $appends = ['time'];
}
