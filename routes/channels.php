<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Authorize per-user evaluation processing private channel. This ensures that
// only the user who started the processing job can subscribe to their
// evaluation-processing.{id} private channel.
Broadcast::channel('evaluation-processing.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Authorize per-user bulk import progress channel
Broadcast::channel('bulk-import.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
