<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'assigned_to',
        'assigned_by',
        'assigned_to_type',
        'assigned_at',
        'due_date',
        'notes',
        'status',
        'progress',
        'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the workout that was assigned.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the user who was assigned the workout.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who assigned the workout.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope to get assignments for trainers.
     */
    public function scopeForTrainers($query)
    {
        return $query->where('assigned_to_type', 'trainer');
    }

    /**
     * Scope to get assignments for clients.
     */
    public function scopeForClients($query)
    {
        return $query->where('assigned_to_type', 'client');
    }

    /**
     * Scope to get active assignments.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['assigned', 'in_progress']);
    }

    /**
     * Check if assignment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }
}