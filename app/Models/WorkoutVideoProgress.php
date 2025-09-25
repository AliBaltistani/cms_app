<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutVideoProgress extends Model
{
    use HasFactory;

    protected $table = 'workout_video_progress';

    protected $fillable = [
        'user_id',
        'workout_id',
        'workout_video_id',
        'watched_duration',
        'is_completed',
        'first_watched_at',
        'last_watched_at',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'first_watched_at' => 'datetime',
        'last_watched_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who watched the video.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workout this progress belongs to.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the workout video this progress tracks.
     */
    public function workoutVideo(): BelongsTo
    {
        return $this->belongsTo(WorkoutVideo::class);
    }

    /**
     * Calculate progress percentage based on watched duration.
     */
    public function getProgressPercentageAttribute(): float
    {
        if (!$this->workoutVideo || !$this->workoutVideo->duration) {
            return 0.0;
        }

        return min(100.0, ($this->watched_duration / $this->workoutVideo->duration) * 100);
    }

    /**
     * Mark video as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'last_watched_at' => now(),
        ]);
    }

    /**
     * Update watching progress.
     */
    public function updateProgress(int $watchedDuration): void
    {
        $this->update([
            'watched_duration' => $watchedDuration,
            'last_watched_at' => now(),
            'first_watched_at' => $this->first_watched_at ?? now(),
        ]);

        // Auto-complete if watched 90% or more
        if ($this->workoutVideo && $this->workoutVideo->duration) {
            $progressPercentage = ($watchedDuration / $this->workoutVideo->duration) * 100;
            if ($progressPercentage >= 90 && !$this->is_completed) {
                $this->markAsCompleted();
            }
        }
    }
}