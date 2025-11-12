<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'client_id', 'trainer_id', 'gateway_id', 'amount', 'currency', 'transaction_id', 'status', 'response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'response' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }
}

