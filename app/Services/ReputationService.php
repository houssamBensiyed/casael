<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\Settlement;
use App\Models\User;

class ReputationService
{
    /**
     * Adjust a user's reputation based on their debt status in a colocation.
     *
     * +1 if the user has no unpaid debt (clean departure)
     * -1 if the user has unpaid debt (departure with debt)
     */
    public function adjustForDeparture(User $user, Colocation $colocation): void
    {
        $hasDebt = Settlement::where('colocation_id', $colocation->id)
            ->where('from_user_id', $user->id)
            ->where('is_paid', false)
            ->exists();

        if ($hasDebt) {
            $user->decrement('reputation');
        } else {
            $user->increment('reputation');
        }
    }
}
