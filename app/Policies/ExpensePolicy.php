<?php

namespace App\Policies;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Only active members of the colocation can create expenses.
     */
    public function create(User $user, Colocation $colocation): bool
    {
        return $colocation->status === 'active'
            && $colocation->activeMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * Only the payer or colocation owner can update an expense.
     */
    public function update(User $user, Expense $expense): bool
    {
        $colocation = $expense->colocation;
        if ($colocation->status !== 'active') {
            return false;
        }

        return $expense->payer_id === $user->id || $user->isOwnerOf($colocation);
    }

    /**
     * Only the payer or colocation owner can delete an expense.
     */
    public function delete(User $user, Expense $expense): bool
    {
        $colocation = $expense->colocation;
        if ($colocation->status !== 'active') {
            return false;
        }

        return $expense->payer_id === $user->id || $user->isOwnerOf($colocation);
    }
}
