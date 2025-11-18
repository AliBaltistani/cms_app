<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\UnitConverter;

class WorkoutExerciseSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workout_exercise_id',
        'set_number',
        'reps',
        'weight',
        'duration',
        'rest_time',
        'notes',
        'is_completed'
    ];

    protected $casts = [
        'set_number' => 'integer',
        'reps' => 'integer',
        'weight' => 'decimal:2',
        'duration' => 'integer',
        'rest_time' => 'integer',
        'is_completed' => 'boolean',
    ];

    /**
     * Get the workout exercise that owns the set.
     */
    public function workoutExercise()
    {
        return $this->belongsTo(WorkoutExercise::class);
    }

    /**
     * Scope a query to order by set number.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('set_number');
    }

    /**
     * Get the formatted weight with unit.
     */
    public function getFormattedWeightAttribute()
    {
        if (!$this->weight) {
            return null;
        }
        $lbs = UnitConverter::kgToLbs((float)$this->weight);
        return $lbs . ' lbs';
    }

    /**
     * Get the formatted duration.
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) {
            return null;
        }
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        if ($minutes > 0) {
            return $minutes . 'm ' . $seconds . 's';
        }
        
        return $seconds . 's';
    }

    /**
     * Get the formatted rest time.
     */
    public function getFormattedRestTimeAttribute()
    {
        if (!$this->rest_time) {
            return null;
        }
        
        return $this->rest_time . 's';
    }
}