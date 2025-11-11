<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvoiceItem Model
 * 
 * Represents a single line item in an invoice.
 * Typically mapped to a Workout with name and price.
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Models
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'workout_id',
        'title',
        'amount',
    ];

    /**
     * Parent invoice relationship.
     *
     * @return BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Linked workout relationship (optional).
     *
     * @return BelongsTo
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}