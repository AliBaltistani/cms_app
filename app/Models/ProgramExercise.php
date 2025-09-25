<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Program Exercise Model
 * 
 * Links exercises from workout library to circuits with tempo, rest, and notes
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ProgramExercise extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'circuit_id',
        'workout_id',
        'order',
        'tempo',
        'rest_interval',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the circuit that owns the program exercise.
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class);
    }

    /**
     * Get the workout (exercise) linked to this program exercise.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the exercise sets for the program exercise.
     */
    public function exerciseSets(): HasMany
    {
        return $this->hasMany(ExerciseSet::class)->orderBy('set_number');
    }

    /**
     * Get the client progress for the program exercise.
     */
    public function clientProgress(): HasMany
    {
        return $this->hasMany(ClientProgress::class);
    }

    /**
     * Scope a query to filter by circuit.
     */
    public function scopeByCircuit($query, $circuitId)
    {
        return $query->where('circuit_id', $circuitId);
    }

    /**
     * Scope a query to order by exercise order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}