<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * FoodDiary Model
 * 
 * Manages client food diary entries for tracking daily meals
 * Allows clients to log meals and track nutritional intake
 * 
 * @package App\Models
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class FoodDiary extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'food_diary';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_id',
        'meal_id',
        'meal_name',
        'meal_type',
        'calories',
        'protein',
        'carbs',
        'fats',
        'logged_at'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'calories' => 'decimal:2',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fats' => 'decimal:2',
        'logged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the client this diary entry belongs to
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the meal this diary entry references (if any)
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(NutritionMeal::class, 'meal_id');
    }

    /**
     * Scope to get entries by client
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get entries for a specific date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('logged_at', $date);
    }

    /**
     * Scope to get entries for a date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get entries for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('logged_at', Carbon::today());
    }

    /**
     * Scope to get entries for this week
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('logged_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Get meal type based on logged time
     * 
     * @return string
     */
    public function getMealTypeAttribute(): string
    {
        $hour = $this->logged_at->hour;
        
        if ($hour >= 5 && $hour < 11) {
            return 'breakfast';
        } elseif ($hour >= 11 && $hour < 16) {
            return 'lunch';
        } elseif ($hour >= 16 && $hour < 21) {
            return 'dinner';
        } else {
            return 'snack';
        }
    }

    /**
     * Get formatted logged date
     * 
     * @return string
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->logged_at->format('d M, Y');
    }

    /**
     * Get formatted logged time
     * 
     * @return string
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->logged_at->format('h:i A');
    }
}