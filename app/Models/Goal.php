<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    
    protected $fillable = [
        'name',
        'status',
        'user_id',
    ];

    // protected $casts = [
    //     'status' => 'boolean',
    // ];

    protected $attributes = [
        'status' => 1, // Default to active
    ];
    protected $hidden = ['deleted_at'];

    /**
     * Get the user that owns the goal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
