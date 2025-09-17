<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NutritionMeal Model
 * 
 * Manages individual meals within nutrition plans
 * Contains meal details, nutritional information, and preparation instructions
 * 
 * @package App\Models
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionMeal extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'nutrition_meals';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'title',
        'description',
        'meal_type',
        'ingredients',
        'instructions',
        'image_url',
        'prep_time',
        'cook_time',
        'servings',
        'calories_per_serving',
        'protein_per_serving',
        'carbs_per_serving',
        'fats_per_serving',
        'sort_order'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'calories_per_serving' => 'decimal:2',
        'protein_per_serving' => 'decimal:2',
        'carbs_per_serving' => 'decimal:2',
        'fats_per_serving' => 'decimal:2',
        'prep_time' => 'integer',
        'cook_time' => 'integer',
        'servings' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the nutrition plan this meal belongs to
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(NutritionPlan::class, 'plan_id');
    }

    /**
     * Get meal-specific macros
     */
    public function macros(): HasMany
    {
        return $this->hasMany(NutritionMacro::class, 'meal_id');
    }

    /**
     * Scope to get meals by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('meal_type', $type);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get total preparation time (prep + cook)
     */
    public function getTotalTimeAttribute()
    {
        return ($this->prep_time ?? 0) + ($this->cook_time ?? 0);
    }

    /**
     * Get formatted preparation time
     */
    public function getPrepTimeFormattedAttribute()
    {
        if (!$this->prep_time) {
            return 'N/A';
        }
        
        $hours = floor($this->prep_time / 60);
        $minutes = $this->prep_time % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
        }
        
        return $minutes . 'm';
    }

    /**
     * Get formatted cooking time
     */
    public function getCookTimeFormattedAttribute()
    {
        if (!$this->cook_time) {
            return 'N/A';
        }
        
        $hours = floor($this->cook_time / 60);
        $minutes = $this->cook_time % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
        }
        
        return $minutes . 'm';
    }

    /**
     * Get meal type display name
     */
    public function getMealTypeDisplayAttribute()
    {
        return match($this->meal_type) {
            'breakfast' => 'Breakfast',
            'lunch' => 'Lunch',
            'dinner' => 'Dinner',
            'snack' => 'Snack',
            'pre_workout' => 'Pre-Workout',
            'post_workout' => 'Post-Workout',
            default => ucfirst(str_replace('_', ' ', $this->meal_type))
        };
    }

    /**
     * Get ingredients as array
     */
    public function getIngredientsArrayAttribute()
    {
        if (!$this->ingredients) {
            return [];
        }
        
        return array_filter(array_map('trim', explode("\n", $this->ingredients)));
    }

    /**
     * Get instructions as array
     */
    public function getInstructionsArrayAttribute()
    {
        if (!$this->instructions) {
            return [];
        }
        
        return array_filter(array_map('trim', explode("\n", $this->instructions)));
    }

    /**
     * Calculate total macros for all servings
     */
    public function getTotalMacrosAttribute()
    {
        return [
            'calories' => ($this->calories_per_serving ?? 0) * $this->servings,
            'protein' => ($this->protein_per_serving ?? 0) * $this->servings,
            'carbs' => ($this->carbs_per_serving ?? 0) * $this->servings,
            'fats' => ($this->fats_per_serving ?? 0) * $this->servings,
        ];
    }
}
