<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Transaction
 *
 * Records payment transactions for invoices.
 */
class Transaction extends Model
{
    protected $fillable = [
        'invoice_id',
        'client_id',
        'trainer_id',
        'payment_method',
        'amount',
        'status',
        'transaction_id',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
