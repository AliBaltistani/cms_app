<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * BlockedTime Model
 * 
 * Handles trainer blocked time slots with reasons and recurring options
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * 
 * @property int $id
 * @property int $trainer_id
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 * @property string|null $reason
 * @property bool $is_recurring
 * @property string|null $recurring_type
 * @property string|null $recurring_end_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BlockedTime extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'date',
        'start_time',
        'end_time',
        'reason',
        'is_recurring',
        'recurring_type',
        'recurring_end_date'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_recurring' => 'boolean',
        'recurring_end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Recurring type constants
     */
    const RECURRING_DAILY = 'daily';
    const RECURRING_WEEKLY = 'weekly';
    const RECURRING_MONTHLY = 'monthly';

    /**
     * Get all recurring types
     * 
     * @return array
     */
    public static function getRecurringTypes(): array
    {
        return [
            self::RECURRING_DAILY => 'Daily',
            self::RECURRING_WEEKLY => 'Weekly',
            self::RECURRING_MONTHLY => 'Monthly'
        ];
    }

    /**
     * Get the trainer that owns the blocked time
     * 
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Scope a query to only include blocked times for a specific trainer
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $trainerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTrainer($query, int $trainerId)
    {
        return $query->where('trainer_id', $trainerId);
    }

    /**
     * Scope a query to only include blocked times for a specific date
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope a query to only include blocked times for a date range
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include recurring blocked times
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope a query to only include active blocked times
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                    ->where(function ($q) {
                        $q->where('is_recurring', false)
                          ->orWhere(function ($subQ) {
                              $subQ->where('is_recurring', true)
                                   ->where(function ($recurQ) {
                                       $recurQ->whereNull('recurring_end_date')
                                              ->orWhere('recurring_end_date', '>=', now()->toDateString());
                                   });
                          });
                    });
    }

    /**
     * Check if the blocked time conflicts with a given time range
     * 
     * @param string $startTime
     * @param string $endTime
     * @return bool
     */
    public function conflictsWith(string $startTime, string $endTime): bool
    {
        $blockedStart = Carbon::createFromFormat('H:i:s', $this->start_time);
        $blockedEnd = Carbon::createFromFormat('H:i:s', $this->end_time);
        $checkStart = Carbon::createFromFormat('H:i:s', $startTime);
        $checkEnd = Carbon::createFromFormat('H:i:s', $endTime);

        return !($checkEnd->lte($blockedStart) || $checkStart->gte($blockedEnd));
    }

    /**
     * Get the duration of the blocked time in minutes
     * 
     * @return int
     */
    public function getDurationInMinutes(): int
    {
        $start = Carbon::createFromFormat('H:i:s', $this->start_time);
        $end = Carbon::createFromFormat('H:i:s', $this->end_time);
        
        return $end->diffInMinutes($start);
    }

    /**
     * Check if the blocked time is still active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        if ($this->date < now()->toDateString()) {
            return false;
        }

        if (!$this->is_recurring) {
            return true;
        }

        return !$this->recurring_end_date || 
               $this->recurring_end_date >= now()->toDateString();
    }

    /**
     * Get the recurring type name
     * 
     * @return string|null
     */
    public function getRecurringTypeName(): ?string
    {
        if (!$this->is_recurring || !$this->recurring_type) {
            return null;
        }

        $types = self::getRecurringTypes();
        return $types[$this->recurring_type] ?? null;
    }
}