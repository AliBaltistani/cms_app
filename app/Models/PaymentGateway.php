<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'public_key', 'secret_key', 'webhook_secret', 'connect_client_id', 'is_default', 'enabled',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'enabled' => 'boolean',
        'secret_key' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'public_key' => 'encrypted',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'gateway_id');
    }
}

