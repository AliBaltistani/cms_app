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
        // Identity
        'full_name', 'dob', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'phone', 'email',
        // Business
        'business_name', 'business_type', 'business_address_line1', 'business_address_line2', 'business_city', 'business_state', 'business_postal_code', 'business_country', 'business_phone', 'business_email', 'tax_id_last4',
        // Bank
        'account_holder_name', 'bank_name', 'bank_account_last4', 'routing_number_last4', 'external_account_id', 'bank_verification_status', 'bank_added_at',
        // Review
        'onboarding_review', 'details_submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dob' => 'date',
        'bank_added_at' => 'datetime',
        'details_submitted_at' => 'datetime',
        'onboarding_review' => 'array',
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