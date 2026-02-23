<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'reputation',
        'is_banned',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'reputation' => 'integer',
        ];
    }

    /**
     * Check if the user is a global admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is banned.
     */
    public function isBanned(): bool
    {
        return $this->is_banned;
    }

    /**
     * Check if the user is the owner of a given colocation.
     */
    public function isOwnerOf($colocation): bool
    {
        return $colocation->owner_id === $this->id;
    }

    /**
     * Get the user's active colocation membership (where left_at is null and colocation is active).
     */
    public function activeColocation()
    {
        return $this->belongsToMany(Colocation::class)
            ->wherePivotNull('left_at')
            ->where('status', 'active')
            ->withPivot('role', 'joined_at', 'left_at')
            ->first();
    }

    /**
     * Check if the user has an active colocation.
     */
    public function hasActiveColocation(): bool
    {
        return $this->activeColocation() !== null;
    }

    /**
     * Get all colocations the user belongs to (past and present).
     */
    public function colocations()
    {
        return $this->belongsToMany(Colocation::class)
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    /**
     * Get all expenses paid by this user.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'payer_id');
    }
}
