<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrainerStripeAccount Model
 * 
 * Stores Stripe Connect account details for a trainer.
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Models
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class TrainerStripeAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'account_id',
        'verification_status',
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