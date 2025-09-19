<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Availability Model
 * 
 * Handles trainer weekly availability settings (morning/evening)
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * 
 * @property int $id
 * @property int $trainer_id
 * @property int $day_of_week
 * @property bool $morning_available
 * @property bool $evening_available
 * @property string|null $morning_start_time
 * @property string|null $morning_end_time
 * @property string|null $evening_start_time
 * @property string|null $evening_end_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Availability extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'day_of_week',
        'morning_available',
        'evening_available',
        'morning_start_time',
        'morning_end_time',
        'evening_start_time',
        'evening_end_time'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'morning_available' => 'boolean',
        'evening_available' => 'boolean',
        'morning_start_time' => 'datetime:H:i',
        'morning_end_time' => 'datetime:H:i',
        'evening_start_time' => 'datetime:H:i',
        'evening_end_time' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Day of week constants
     */
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    /**
     * Get all days of the week
     * 
     * @return array
     */
    public static function getDaysOfWeek(): array
    {
        return [
            self::SUNDAY => 'Sunday',
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday'
        ];
    }

    /**
     * Get the trainer that owns the availability
     * 
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Scope a query to only include availability for a specific trainer
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
     * Scope a query to only include availability for a specific day
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $dayOfWeek
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope a query to only include morning availability
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMorningAvailable($query)
    {
        return $query->where('morning_available', true);
    }

    /**
     * Scope a query to only include evening availability
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEveningAvailable($query)
    {
        return $query->where('evening_available', true);
    }

    /**
     * Get the day name
     * 
     * @return string
     */
    public function getDayName(): string
    {
        $days = self::getDaysOfWeek();
        return $days[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Check if trainer is available in the morning
     * 
     * @return bool
     */
    public function isMorningAvailable(): bool
    {
        return $this->morning_available && 
               $this->morning_start_time && 
               $this->morning_end_time;
    }

    /**
     * Check if trainer is available in the evening
     * 
     * @return bool
     */
    public function isEveningAvailable(): bool
    {
        return $this->evening_available && 
               $this->evening_start_time && 
               $this->evening_end_time;
    }

    /**
     * Get available time slots for the day
     * 
     * @return array
     */
    public function getAvailableTimeSlots(): array
    {
        $slots = [];
        
        if ($this->isMorningAvailable()) {
            $slots['morning'] = [
                'start' => $this->morning_start_time,
                'end' => $this->morning_end_time
            ];
        }
        
        if ($this->isEveningAvailable()) {
            $slots['evening'] = [
                'start' => $this->evening_start_time,
                'end' => $this->evening_end_time
            ];
        }
        
        return $slots;
    }
}