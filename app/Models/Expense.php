<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'amount',
        'date',
        'category_id',
        'payer_id',
        'colocation_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    /**
     * Get the user who paid this expense.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Get the colocation this expense belongs to.
     */
    public function colocation(): BelongsTo
    {
        return $this->belongsTo(Colocation::class);
    }

    /**
     * Get the category of this expense.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
