<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'colocation_id',
    ];

    /**
     * Get the colocation this category belongs to.
     */
    public function colocation(): BelongsTo
    {
        return $this->belongsTo(Colocation::class);
    }

    /**
     * Get all expenses in this category.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
