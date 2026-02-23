<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    /**
     * Only the colocation owner can send invitations, and the colocation must be active.
     */
    public function create(User $user, Colocation $colocation): bool
    {
        return $user->isOwnerOf($colocation) && $colocation->status === 'active';
    }

    /**
     * Only the colocation owner can cancel (delete) a pending invitation.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return $user->isOwnerOf($invitation->colocation) && $invitation->status === 'pending';
    }
}
