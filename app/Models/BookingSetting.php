<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * BookingSetting Model
 * 
 * Handles trainer booking preferences and approval settings
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * 
 * @property int $id
 * @property int $trainer_id
 * @property bool $allow_self_booking
 * @property bool $require_approval
 * @property int $advance_booking_days
 * @property int $cancellation_hours
 * @property bool $allow_weekend_booking
 * @property string $earliest_booking_time
 * @property string $latest_booking_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookingSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'allow_self_booking',
        'require_approval',
        'advance_booking_days',
        'cancellation_hours',
        'allow_weekend_booking',
        'earliest_booking_time',
        'latest_booking_time'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allow_self_booking' => 'boolean',
        'require_approval' => 'boolean',
        'allow_weekend_booking' => 'boolean',
        'advance_booking_days' => 'integer',
        'cancellation_hours' => 'integer',
        'earliest_booking_time' => 'datetime:H:i',
        'latest_booking_time' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Default values
     */
    const DEFAULT_ADVANCE_BOOKING_DAYS = 30;
    const DEFAULT_CANCELLATION_HOURS = 24;
    const DEFAULT_EARLIEST_TIME = '06:00:00';
    const DEFAULT_LATEST_TIME = '22:00:00';

    /**
     * Get the trainer that owns the booking setting
     * 
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Scope a query to only include settings for a specific trainer
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
     * Get or create booking settings for a trainer
     * 
     * @param int $trainerId
     * @return BookingSetting
     */
    public static function getOrCreateForTrainer(int $trainerId): BookingSetting
    {
        return self::firstOrCreate(
            ['trainer_id' => $trainerId],
            [
                'allow_self_booking' => true,
                'require_approval' => false,
                'advance_booking_days' => self::DEFAULT_ADVANCE_BOOKING_DAYS,
                'cancellation_hours' => self::DEFAULT_CANCELLATION_HOURS,
                'allow_weekend_booking' => true,
                'earliest_booking_time' => self::DEFAULT_EARLIEST_TIME,
                'latest_booking_time' => self::DEFAULT_LATEST_TIME
            ]
        );
    }

    /**
     * Check if booking is allowed for a specific date and time
     * 
     * @param string $date
     * @param string $time
     * @return bool
     */
    public function isBookingAllowed(string $date, string $time): bool
    {
        // Check if self booking is allowed
        if (!$this->allow_self_booking) {
            return false;
        }

        // Check advance booking limit
        $bookingDate = Carbon::parse($date);
        $maxAdvanceDate = now()->addDays($this->advance_booking_days);
        
        if ($bookingDate->gt($maxAdvanceDate)) {
            return false;
        }

        // Check if booking is in the past
        if ($bookingDate->lt(now()->toDateString())) {
            return false;
        }

        // Check weekend booking
        if (!$this->allow_weekend_booking && $bookingDate->isWeekend()) {
            return false;
        }

        // Check time range
        $bookingTime = Carbon::createFromFormat('H:i:s', $time);
        $earliestTime = Carbon::createFromFormat('H:i:s', $this->earliest_booking_time);
        $latestTime = Carbon::createFromFormat('H:i:s', $this->latest_booking_time);

        return $bookingTime->between($earliestTime, $latestTime);
    }

    /**
     * Check if cancellation is allowed for a specific schedule
     * 
     * @param Schedule $schedule
     * @return bool
     */
    public function isCancellationAllowed(Schedule $schedule): bool
    {
        $scheduleDateTime = Carbon::parse($schedule->date . ' ' . $schedule->start_time);
        $cancellationDeadline = $scheduleDateTime->subHours($this->cancellation_hours);

        return now()->lt($cancellationDeadline);
    }

    /**
     * Get the latest allowed booking date
     * 
     * @return Carbon
     */
    public function getLatestBookingDate(): Carbon
    {
        return now()->addDays($this->advance_booking_days);
    }

    /**
     * Get cancellation deadline for a schedule
     * 
     * @param Schedule $schedule
     * @return Carbon
     */
    public function getCancellationDeadline(Schedule $schedule): Carbon
    {
        $scheduleDateTime = Carbon::parse($schedule->date . ' ' . $schedule->start_time);
        return $scheduleDateTime->subHours($this->cancellation_hours);
    }

    /**
     * Check if time is within booking hours
     * 
     * @param string $time
     * @return bool
     */
    public function isTimeWithinBookingHours(string $time): bool
    {
        $checkTime = Carbon::createFromFormat('H:i:s', $time);
        $earliestTime = Carbon::createFromFormat('H:i:s', $this->earliest_booking_time);
        $latestTime = Carbon::createFromFormat('H:i:s', $this->latest_booking_time);

        return $checkTime->between($earliestTime, $latestTime);
    }

    /**
     * Get booking rules summary
     * 
     * @return array
     */
    public function getBookingRules(): array
    {
        return [
            'self_booking_allowed' => $this->allow_self_booking,
            'requires_approval' => $this->require_approval,
            'advance_booking_limit' => $this->advance_booking_days . ' days',
            'cancellation_policy' => $this->cancellation_hours . ' hours before session',
            'weekend_booking' => $this->allow_weekend_booking ? 'Allowed' : 'Not allowed',
            'booking_hours' => $this->earliest_booking_time . ' - ' . $this->latest_booking_time
        ];
    }

    /**
     * Validate booking settings
     * 
     * @return array Validation errors
     */
    public function validateSettings(): array
    {
        $errors = [];

        if ($this->advance_booking_days <= 0) {
            $errors[] = 'Advance booking days must be greater than 0';
        }

        if ($this->cancellation_hours < 0) {
            $errors[] = 'Cancellation hours cannot be negative';
        }

        $earliestTime = Carbon::createFromFormat('H:i:s', $this->earliest_booking_time);
        $latestTime = Carbon::createFromFormat('H:i:s', $this->latest_booking_time);

        if ($earliestTime->gte($latestTime)) {
            $errors[] = 'Earliest booking time must be before latest booking time';
        }

        return $errors;
    }
}