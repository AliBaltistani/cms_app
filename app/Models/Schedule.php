<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Schedule Model
 * 
 * Handles trainer-client booking schedules with status management
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * 
 * @property int $id
 * @property int $trainer_id
 * @property int $client_id
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 * @property string $status
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'client_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'notes'
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get all available statuses
     * 
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }

    /**
     * Get the trainer that owns the schedule
     * 
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Get the client that owns the schedule
     * 
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Scope a query to only include schedules for a specific trainer
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
     * Scope a query to only include schedules for a specific client
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $clientId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope a query to only include schedules with a specific status
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include schedules for a specific date range
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
     * Check if the schedule can be cancelled
     * 
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return $this->status !== self::STATUS_CANCELLED && 
               $this->date >= now()->toDateString();
    }

    /**
     * Check if the schedule is confirmed
     * 
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Check if the schedule is pending
     * 
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get the duration of the schedule in minutes
     * 
     * @return int
     */
    public function getDurationInMinutes(): int
    {
        // Since start_time and end_time are cast as datetime, they are Carbon instances
        // We need to create new Carbon instances for today with just the time portion
        $start = Carbon::today()->setTimeFromTimeString($this->start_time->format('H:i:s'));
        $end = Carbon::today()->setTimeFromTimeString($this->end_time->format('H:i:s'));
        
        return $end->diffInMinutes($start);
    }
}