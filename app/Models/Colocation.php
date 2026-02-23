<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'owner_id',
    ];

    /**
     * Scope to only active colocations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the owner of the colocation.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all members (past and present) of the colocation.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    /**
     * Get only active members (left_at is null).
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->wherePivotNull('left_at')
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    /**
     * Get all invitations for this colocation.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get all categories for this colocation.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get all expenses for this colocation.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get all settlements for this colocation.
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }
}
