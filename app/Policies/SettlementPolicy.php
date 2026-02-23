<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\Settlement;
use App\Models\User;

class SettlementPolicy
{
    /**
     * Only active members can view balances / settlements.
     */
    public function viewBalances(User $user, Colocation $colocation): bool
    {
        return $colocation->activeMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * Only the colocation owner can generate settlements.
     */
    public function generate(User $user, Colocation $colocation): bool
    {
        return $colocation->status === 'active'
            && $user->isOwnerOf($colocation);
    }

    /**
     * The creditor (to_user) or the colocation owner can mark a settlement as paid.
     */
    public function markPaid(User $user, Settlement $settlement): bool
    {
        if ($settlement->is_paid) {
            return false;
        }

        return $settlement->to_user_id === $user->id
            || $user->isOwnerOf($settlement->colocation);
    }
}
