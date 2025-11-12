<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_name',
        'gateway_type',
        'public_key',
        'secret_key',
        'webhook_secret',
        'account_id',
        'is_default',
        'status',
        'commission_rate',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'status' => 'boolean',
        'commission_rate' => 'float',
        'secret_key' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('status', true);
    }
}
