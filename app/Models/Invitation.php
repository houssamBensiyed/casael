<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'colocation_id',
        'email',
        'token',
        'status',
    ];

    /**
     * Get the colocation this invitation belongs to.
     */
    public function colocation(): BelongsTo
    {
        return $this->belongsTo(Colocation::class);
    }
}
