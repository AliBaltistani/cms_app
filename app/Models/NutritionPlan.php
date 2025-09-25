<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NutritionPlan Model
 * 
 * Manages nutrition plans with role-based access control
 * Admin: Full CRUD access to all plans
 * Trainer: Can create plans for assigned trainees
 * Trainee: Read-only access to assigned plans
 * 
 * @package App\Models
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionPlan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'nutrition_plans';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'trainer_id',
        'client_id',
        'category',
        'plan_name',
        'name',
        'description',
        'image_url',
        'status',
        'is_global',
        'tags',
        'duration_days',
        'target_weight',
        'goal_type'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tags' => 'array',
        'is_global' => 'boolean',
        'target_weight' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the trainer that created this plan
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Get the client assigned to this plan
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get all meals for this nutrition plan
     */
    public function meals(): HasMany
    {
        return $this->hasMany(NutritionMeal::class, 'plan_id')->orderBy('sort_order');
    }

    /**
     * Get macros for this nutrition plan
     */
    public function macros(): HasMany
    {
        return $this->hasMany(NutritionMacro::class, 'plan_id');
    }

    /**
     * Get daily macro targets for this plan
     */
    public function dailyMacros(): HasOne
    {
        return $this->hasOne(NutritionMacro::class, 'plan_id')
                   ->where('macro_type', 'daily_target');
    }

    /**
     * Get restrictions for this nutrition plan
     */
    public function restrictions(): HasOne
    {
        return $this->hasOne(NutritionRestriction::class, 'plan_id');
    }

    /**
     * Get nutrition recommendations for this plan
     */
    public function recommendations(): HasOne
    {
        return $this->hasOne(NutritionRecommendation::class, 'plan_id');
    }

    /**
     * Get food diary entries for this plan's client
     */
    public function foodDiaryEntries(): HasMany
    {
        return $this->hasMany(FoodDiary::class, 'client_id', 'client_id');
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get global plans
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            'weight_loss' => 'Weight Loss',
            'muscle_gain' => 'Muscle Gain',
            'wellness' => 'Wellness',
            'maintenance' => 'Maintenance',
            'athletic_performance' => 'Athletic Performance'
        ];
    }

    /**
     * Scope to get active plans only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get plans by trainer
     */
    public function scopeByTrainer($query, $trainerId)
    {
        return $query->where('trainer_id', $trainerId);
    }

    /**
     * Scope to get plans by client
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Get total calories from all meals
     */
    public function getTotalCaloriesAttribute()
    {
        return $this->meals->sum('calories_per_serving');
    }

    /**
     * Get plan duration in human readable format
     */
    public function getDurationTextAttribute()
    {
        if (!$this->duration_days) {
            return 'Ongoing';
        }
        
        $days = $this->duration_days;
        if ($days < 7) {
            return $days . ' day' . ($days > 1 ? 's' : '');
        } elseif ($days < 30) {
            $weeks = floor($days / 7);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '');
        } else {
            $months = floor($days / 30);
            return $months . ' month' . ($months > 1 ? 's' : '');
        }
    }

    /**
     * Check if plan can be edited by user
     */
    public function canBeEditedBy(User $user): bool
    {
        // Admin can edit all plans
        if ($user->role === 'admin') {
            return true;
        }
        
        // Trainers can only edit their own plans (not global ones)
        if ($user->role === 'trainer') {
            return $this->trainer_id === $user->id && !$this->is_global;
        }
        
        // Clients cannot edit plans
        return false;
    }

    /**
     * Check if plan can be viewed by user
     */
    public function canBeViewedBy(User $user): bool
    {
        // Admin can view all plans
        if ($user->role === 'admin') {
            return true;
        }
        
        // Trainers can view their own plans and global plans
        if ($user->role === 'trainer') {
            return $this->trainer_id === $user->id || $this->is_global;
        }
        
        // Clients can only view their assigned plans
        if ($user->role === 'client') {
            return $this->client_id === $user->id;
        }
        
        return false;
    }
}
