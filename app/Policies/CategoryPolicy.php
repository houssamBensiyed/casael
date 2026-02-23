<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Only active members of the colocation can manage categories.
     */
    public function create(User $user, Colocation $colocation): bool
    {
        return $colocation->status === 'active'
            && $colocation->activeMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * Only active members of the category's colocation can update it.
     */
    public function update(User $user, Category $category): bool
    {
        $colocation = $category->colocation;
        return $colocation->status === 'active'
            && $colocation->activeMembers()->where('user_id', $user->id)->exists();
    }

    /**
     * Only the colocation owner can delete a category.
     */
    public function delete(User $user, Category $category): bool
    {
        $colocation = $category->colocation;
        return $colocation->status === 'active'
            && $user->isOwnerOf($colocation);
    }
}
