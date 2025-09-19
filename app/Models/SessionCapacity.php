<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * SessionCapacity Model
 * 
 * Handles trainer session capacity limits and duration settings
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * 
 * @property int $id
 * @property int $trainer_id
 * @property int $max_daily_sessions
 * @property int $max_weekly_sessions
 * @property int $session_duration_minutes
 * @property int $break_between_sessions_minutes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SessionCapacity extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'session_capacity';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'max_daily_sessions',
        'max_weekly_sessions',
        'session_duration_minutes',
        'break_between_sessions_minutes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'max_daily_sessions' => 'integer',
        'max_weekly_sessions' => 'integer',
        'session_duration_minutes' => 'integer',
        'break_between_sessions_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Default values
     */
    const DEFAULT_MAX_DAILY_SESSIONS = 8;
    const DEFAULT_MAX_WEEKLY_SESSIONS = 40;
    const DEFAULT_SESSION_DURATION = 60; // minutes
    const DEFAULT_BREAK_DURATION = 15; // minutes

    /**
     * Get the trainer that owns the session capacity
     * 
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Scope a query to only include capacity for a specific trainer
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
     * Get or create session capacity for a trainer
     * 
     * @param int $trainerId
     * @return SessionCapacity
     */
    public static function getOrCreateForTrainer(int $trainerId): SessionCapacity
    {
        return self::firstOrCreate(
            ['trainer_id' => $trainerId],
            [
                'max_daily_sessions' => self::DEFAULT_MAX_DAILY_SESSIONS,
                'max_weekly_sessions' => self::DEFAULT_MAX_WEEKLY_SESSIONS,
                'session_duration_minutes' => self::DEFAULT_SESSION_DURATION,
                'break_between_sessions_minutes' => self::DEFAULT_BREAK_DURATION
            ]
        );
    }

    /**
     * Check if trainer can accept more sessions on a specific date
     * 
     * @param string $date
     * @return bool
     */
    public function canAcceptMoreSessionsOnDate(string $date): bool
    {
        $existingSessions = Schedule::forTrainer($this->trainer_id)
            ->forDate($date)
            ->withStatus(Schedule::STATUS_CONFIRMED)
            ->count();

        return $existingSessions < $this->max_daily_sessions;
    }

    /**
     * Check if trainer can accept more sessions in a specific week
     * 
     * @param string $date
     * @return bool
     */
    public function canAcceptMoreSessionsInWeek(string $date): bool
    {
        $startOfWeek = Carbon::parse($date)->startOfWeek()->toDateString();
        $endOfWeek = Carbon::parse($date)->endOfWeek()->toDateString();

        $existingSessions = Schedule::forTrainer($this->trainer_id)
            ->dateRange($startOfWeek, $endOfWeek)
            ->withStatus(Schedule::STATUS_CONFIRMED)
            ->count();

        return $existingSessions < $this->max_weekly_sessions;
    }

    /**
     * Get remaining daily sessions for a specific date
     * 
     * @param string $date
     * @return int
     */
    public function getRemainingDailySessions(string $date): int
    {
        $existingSessions = Schedule::forTrainer($this->trainer_id)
            ->forDate($date)
            ->withStatus(Schedule::STATUS_CONFIRMED)
            ->count();

        return max(0, $this->max_daily_sessions - $existingSessions);
    }

    /**
     * Get remaining weekly sessions for a specific week
     * 
     * @param string $date
     * @return int
     */
    public function getRemainingWeeklySessions(string $date): int
    {
        $startOfWeek = Carbon::parse($date)->startOfWeek()->toDateString();
        $endOfWeek = Carbon::parse($date)->endOfWeek()->toDateString();

        $existingSessions = Schedule::forTrainer($this->trainer_id)
            ->dateRange($startOfWeek, $endOfWeek)
            ->withStatus(Schedule::STATUS_CONFIRMED)
            ->count();

        return max(0, $this->max_weekly_sessions - $existingSessions);
    }

    /**
     * Get total session duration including break time
     * 
     * @return int Duration in minutes
     */
    public function getTotalSessionDuration(): int
    {
        return $this->session_duration_minutes + $this->break_between_sessions_minutes;
    }

    /**
     * Calculate maximum working hours per day
     * 
     * @return float Hours
     */
    public function getMaxDailyWorkingHours(): float
    {
        $totalMinutes = $this->max_daily_sessions * $this->session_duration_minutes;
        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate maximum working hours per week
     * 
     * @return float Hours
     */
    public function getMaxWeeklyWorkingHours(): float
    {
        $totalMinutes = $this->max_weekly_sessions * $this->session_duration_minutes;
        return round($totalMinutes / 60, 2);
    }

    /**
     * Validate session capacity settings
     * 
     * @return array Validation errors
     */
    public function validateCapacity(): array
    {
        $errors = [];

        if ($this->max_daily_sessions <= 0) {
            $errors[] = 'Maximum daily sessions must be greater than 0';
        }

        if ($this->max_weekly_sessions <= 0) {
            $errors[] = 'Maximum weekly sessions must be greater than 0';
        }

        if ($this->max_daily_sessions * 7 > $this->max_weekly_sessions) {
            $errors[] = 'Weekly session limit is too low for daily session limit';
        }

        if ($this->session_duration_minutes <= 0) {
            $errors[] = 'Session duration must be greater than 0 minutes';
        }

        if ($this->break_between_sessions_minutes < 0) {
            $errors[] = 'Break duration cannot be negative';
        }

        return $errors;
    }
}