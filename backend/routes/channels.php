<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('Lab.{id}', function ($user, $id) {
    // Staff yang punya akses ke lab ini
    if ($user->hasRole('super_admin')) {
        return true;
    }
    return $user->labs()->where('lab_id', $id)->exists();
});
