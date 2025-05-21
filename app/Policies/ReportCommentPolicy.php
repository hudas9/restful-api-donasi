<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReportComment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportCommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->role === 'admin';
    }

    public function update(User $user, ReportComment $comment)
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, ReportComment $comment)
    {
        return $user->role === 'admin' || $user->id === $comment->user_id;
    }
}
