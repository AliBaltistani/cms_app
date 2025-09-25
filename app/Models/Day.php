<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Day Model
 * 
 * Represents workout days within program weeks (Day 1, Day 2, etc.)
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class Day extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'week_id',
        'day_number',
        'title',
        'description',
        'cool_down',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'day_number' => 'integer',
    ];

    /**
     * Get the week that owns the day.
     */
    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    /**
     * Get the circuits for the day.
     */
    public function circuits(): HasMany
    {
        return $this->hasMany(Circuit::class)->orderBy('circuit_number');
    }

    /**
     * Get the formatted day title.
     */
    public function getFormattedTitleAttribute(): string
    {
        return "Day {$this->day_number}" . ($this->title ? " → {$this->title}" : '');
    }

    /**
     * Scope a query to filter by week.
     */
    public function scopeByWeek($query, $weekId)
    {
        return $query->where('week_id', $weekId);
    }
}