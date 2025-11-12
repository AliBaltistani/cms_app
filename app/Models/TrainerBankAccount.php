<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainer_id', 'gateway', 'account_id', 'display_name', 'country', 'verification_status', 'last_status_sync_at', 'raw_meta',
    ];

    protected $casts = [
        'last_status_sync_at' => 'datetime',
        'raw_meta' => 'array',
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}

