<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Invoice Model
 * 
 * Represents a workout-based invoice between a trainer and client.
 * Handles totals, status, and transaction linkage.
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Models
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'client_id',
        'total_amount',
        'status',
        'payment_method',
        'transaction_id',
        'commission_amount',
        'net_amount',
        'due_date',
        'note',
    ];

    /**
     * Attribute casting for date fields.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Trainer relationship (user with role trainer).
     *
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Client relationship (user with role client).
     *
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Invoice items relationship.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}