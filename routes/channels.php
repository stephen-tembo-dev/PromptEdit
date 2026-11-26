<?php

use App\Models\User\User;
use Illuminate\Support\Facades\Broadcast;

// Each user hears about their own pictures and nobody else's.
Broadcast::channel('users.{userId}', fn (User $user, string $userId): bool => (string) $user->id === $userId);
