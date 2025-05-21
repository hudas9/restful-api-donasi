<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProgramComment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProgramCommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->role === 'admin';
    }

    public function update(User $user, ProgramComment $comment)
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, ProgramComment $comment)
    {
        return $user->role === 'admin' || $user->id === $comment->user_id;
    }
}
