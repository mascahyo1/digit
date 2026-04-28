<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel untuk 1-on-1 chat antara dua user
// Format channel: chat.{kecil_id}-{besar_id}
Broadcast::channel('chat.{userA}-{userB}', function ($user, $userA, $userB) {
    // User yang login harus merupakan salah satu dari dua user di channel
    return in_array($user->id, [(int) $userA, (int) $userB]);
});

// Private channel notifikasi per user
// Hanya user yang bersangkutan yang boleh subscribe
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
