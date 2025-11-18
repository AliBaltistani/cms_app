<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Client Progress Model
 * 
 * Tracks client progress on program exercises including logged reps and weight
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ClientProgress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'client_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'program_exercise_id',
        'set_number',
        'status',
        'logged_reps',
        'logged_weight',
        'notes',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'set_number' => 'integer',
        'logged_reps' => 'integer',
        'logged_weight' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the client that owns the progress record.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the program exercise that this progress belongs to.
     */
    public function programExercise(): BelongsTo
    {
        return $this->belongsTo(ProgramExercise::class);
    }

    /**
     * Get the formatted progress display.
     */
    public function getFormattedProgressAttribute(): string
    {
        $display = "Set {$this->set_number} - {$this->status}";
        
        if ($this->status === 'completed' && ($this->logged_reps || $this->logged_weight)) {
            $details = [];
            if ($this->logged_reps) $details[] = "{$this->logged_reps} reps";
            if ($this->logged_weight) {
                $lbs = UnitConverter::kgToLbs((float)$this->logged_weight);
                $details[] = "{$lbs} lbs";
            }
            $display .= " (" . implode(', ', $details) . ")";
        }
        
        return $display;
    }

    /**
     * Scope a query to filter by client.
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter completed progress.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to filter skipped progress.
     */
    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }
}
use App\Support\UnitConverter;