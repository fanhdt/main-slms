<?php

use Illuminate\Support\Facades\Broadcast;

// Channel personal user — dipakai untuk notifikasi pribadi (booking, foto, dll)
// Setiap user hanya boleh subscribe ke channel miliknya sendiri.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('Lab.{id}', function ($user, $id) {
    // Staff yang punya akses ke lab ini
    if ($user->hasRole('super_admin')) {
        return true;
    }
    return $user->labs()->where('lab_id', $id)->exists();
});