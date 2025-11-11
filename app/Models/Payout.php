<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payout Model
 * 
 * Tracks trainer payouts resulting from paid invoices.
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Models
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class Payout extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'amount',
        'payout_status',
        'stripe_payout_id',
    ];

    /**
     * Trainer relationship.
     *
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}