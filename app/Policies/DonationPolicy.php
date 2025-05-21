<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Donation;
use Illuminate\Auth\Access\HandlesAuthorization;

class DonationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->role === 'admin';
    }

    public function view(User $user, Donation $donation)
    {
        return $user->role === 'admin' || $user->id === $donation->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Donation $donation)
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Donation $donation)
    {
        return $user->role === 'admin';
    }
}
