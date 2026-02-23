<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\User;

class ColocationPolicy
{
    /**
     * Determine if the user can view the colocation.
     * Any current or past member can view.
     */
    public function view(User $user, Colocation $colocation): bool
    {
        return $colocation->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can update the colocation.
     * Only the owner of an active colocation.
     */
    public function update(User $user, Colocation $colocation): bool
    {
        return $user->isOwnerOf($colocation) && $colocation->status === 'active';
    }

    /**
     * Determine if the user can delete (cancel) the colocation.
     * Only the owner of an active colocation.
     */
    public function delete(User $user, Colocation $colocation): bool
    {
        return $user->isOwnerOf($colocation) && $colocation->status === 'active';
    }

    /**
     * Determine if the user can leave the colocation.
     * Active member but NOT the owner.
     */
    public function leave(User $user, Colocation $colocation): bool
    {
        if ($colocation->status !== 'active') {
            return false;
        }

        if ($user->isOwnerOf($colocation)) {
            return false;
        }

        return $colocation->activeMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can remove a member from the colocation.
     * Only the owner of an active colocation.
     */
    public function removeMember(User $user, Colocation $colocation): bool
    {
        return $user->isOwnerOf($colocation) && $colocation->status === 'active';
    }
}
