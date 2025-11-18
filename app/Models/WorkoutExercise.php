<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\UnitConverter;

class WorkoutExercise extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workout_id',
        'exercise_id',
        'order',
        'sets',
        'reps',
        'weight',
        'duration',
        'rest_interval',
        'tempo',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'sets' => 'integer',
        'reps' => 'integer',
        'weight' => 'decimal:2',
        'duration' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the workout that owns the exercise.
     */
    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the exercise details.
     */
    public function exercise()
    {
        return $this->belongsTo(Workout::class, 'exercise_id');
    }

    /**
     * Get the exercise sets for this workout exercise.
     */
    public function exerciseSets()
    {
        return $this->hasMany(WorkoutExerciseSet::class, 'workout_exercise_id');
    }

    /**
     * Scope a query to only include active exercises.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by exercise order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get the formatted rest interval.
     */
    public function getFormattedRestIntervalAttribute()
    {
        if (!$this->rest_interval) {
            return null;
        }
        
        return $this->rest_interval . 's';
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
     * Get the exercise name from the related exercise.
     */
    public function getExerciseNameAttribute()
    {
        return $this->exercise ? $this->exercise->name : 'Unknown Exercise';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workoutExercise) {
            if (is_null($workoutExercise->order)) {
                $workoutExercise->order = static::where('workout_id', $workoutExercise->workout_id)->max('order') + 1;
            }
        });
    }
}