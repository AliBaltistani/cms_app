<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    
    protected $fillable = [
        'name',
        'status',
    ];

    // protected $casts = [
    //     'status' => 'boolean',
    // ];

    protected $attributes = [
        'status' => 1, // Default to active
    ];
    protected $hidden = ['deleted_at'];
}
