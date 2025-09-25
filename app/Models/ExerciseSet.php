<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exercise Set Model
 * 
 * Stores sets (Set 1-5) with reps and weight for each program exercise
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ExerciseSet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_exercise_id',
        'set_number',
        'reps',
        'weight',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'set_number' => 'integer',
        'reps' => 'integer',
        'weight' => 'decimal:2',
    ];

    /**
     * Get the program exercise that owns the exercise set.
     */
    public function programExercise(): BelongsTo
    {
        return $this->belongsTo(ProgramExercise::class);
    }

    /**
     * Get the formatted set display.
     */
    public function getFormattedSetAttribute(): string
    {
        $display = "Set {$this->set_number}";
        
        if ($this->reps && $this->weight) {
            $display .= ": {$this->reps} reps @ {$this->weight}kg";
        } elseif ($this->reps) {
            $display .= ": {$this->reps} reps";
        } elseif ($this->weight) {
            $display .= ": {$this->weight}kg";
        }
        
        return $display;
    }

    /**
     * Scope a query to filter by program exercise.
     */
    public function scopeByProgramExercise($query, $programExerciseId)
    {
        return $query->where('program_exercise_id', $programExerciseId);
    }

    /**
     * Scope a query to order by set number.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('set_number');
    }
}