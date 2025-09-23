<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Circuit Model
 * 
 * Represents circuits within workout days (Circuit 1, Circuit 2, etc.)
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class Circuit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'day_id',
        'circuit_number',
        'title',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'circuit_number' => 'integer',
    ];

    /**
     * Get the day that owns the circuit.
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(Day::class);
    }

    /**
     * Get the program exercises for the circuit.
     */
    public function programExercises(): HasMany
    {
        return $this->hasMany(ProgramExercise::class)->orderBy('order');
    }

    /**
     * Get the formatted circuit title.
     */
    public function getFormattedTitleAttribute(): string
    {
        return $this->title ?: "Circuit {$this->circuit_number}";
    }

    /**
     * Scope a query to filter by day.
     */
    public function scopeByDay($query, $dayId)
    {
        return $query->where('day_id', $dayId);
    }
}