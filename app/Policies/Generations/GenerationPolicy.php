<?php

namespace App\Policies\Generations;

use App\Models\Generations\Generation;
use App\Models\User\User;

/**
 * Who is allowed to do what with a picture. Users can only see their own.
 */
class GenerationPolicy
{
    /**
     * True if this user owns the picture.
     */
    public function view(User $user, Generation $generation): bool
    {
        return $generation->user_id === $user->id;
    }
}
