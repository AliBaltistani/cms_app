<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NutritionMacro Model
 * 
 * Manages macronutrient information for nutrition plans and meals
 * Tracks protein, carbs, fats, calories, and other nutritional data
 * 
 * @package App\Models
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionMacro extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'nutrition_macros';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'meal_id',
        'protein',
        'carbs',
        'fats',
        'total_calories',
        'fiber',
        'sugar',
        'sodium',
        'water',
        'macro_type',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fats' => 'decimal:2',
        'total_calories' => 'decimal:2',
        'fiber' => 'decimal:2',
        'sugar' => 'decimal:2',
        'sodium' => 'decimal:2',
        'water' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the nutrition plan this macro belongs to
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(NutritionPlan::class, 'plan_id');
    }

    /**
     * Get the meal this macro belongs to (if meal-specific)
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(NutritionMeal::class, 'meal_id');
    }

    /**
     * Scope to get daily target macros
     */
    public function scopeDailyTarget($query)
    {
        return $query->where('macro_type', 'daily_target');
    }

    /**
     * Scope to get meal-specific macros
     */
    public function scopeMealSpecific($query)
    {
        return $query->where('macro_type', 'meal_specific');
    }

    /**
     * Calculate calories from macronutrients
     * Protein: 4 cal/g, Carbs: 4 cal/g, Fats: 9 cal/g
     */
    public function getCalculatedCaloriesAttribute()
    {
        return ($this->protein * 4) + ($this->carbs * 4) + ($this->fats * 9);
    }

    /**
     * Get protein percentage of total calories
     */
    public function getProteinPercentageAttribute()
    {
        if ($this->total_calories <= 0) {
            return 0;
        }
        
        return round(($this->protein * 4 / $this->total_calories) * 100, 1);
    }

    /**
     * Get carbs percentage of total calories
     */
    public function getCarbsPercentageAttribute()
    {
        if ($this->total_calories <= 0) {
            return 0;
        }
        
        return round(($this->carbs * 4 / $this->total_calories) * 100, 1);
    }

    /**
     * Get fats percentage of total calories
     */
    public function getFatsPercentageAttribute()
    {
        if ($this->total_calories <= 0) {
            return 0;
        }
        
        return round(($this->fats * 9 / $this->total_calories) * 100, 1);
    }

    /**
     * Get macro distribution as array
     */
    public function getMacroDistributionAttribute()
    {
        return [
            'protein' => [
                'grams' => $this->protein,
                'calories' => $this->protein * 4,
                'percentage' => $this->protein_percentage
            ],
            'carbs' => [
                'grams' => $this->carbs,
                'calories' => $this->carbs * 4,
                'percentage' => $this->carbs_percentage
            ],
            'fats' => [
                'grams' => $this->fats,
                'calories' => $this->fats * 9,
                'percentage' => $this->fats_percentage
            ]
        ];
    }

    /**
     * Check if macros are balanced (within healthy ranges)
     */
    public function getIsBalancedAttribute()
    {
        $proteinPct = $this->protein_percentage;
        $carbsPct = $this->carbs_percentage;
        $fatsPct = $this->fats_percentage;
        
        // Healthy ranges: Protein 10-35%, Carbs 45-65%, Fats 20-35%
        return $proteinPct >= 10 && $proteinPct <= 35 &&
               $carbsPct >= 45 && $carbsPct <= 65 &&
               $fatsPct >= 20 && $fatsPct <= 35;
    }

    /**
     * Get macro type display name
     */
    public function getMacroTypeDisplayAttribute()
    {
        return match($this->macro_type) {
            'daily_target' => 'Daily Target',
            'meal_specific' => 'Meal Specific',
            default => ucfirst(str_replace('_', ' ', $this->macro_type))
        };
    }

    /**
     * Format sodium value with unit
     */
    public function getSodiumFormattedAttribute()
    {
        if (!$this->sodium) {
            return 'N/A';
        }
        
        return $this->sodium >= 1000 
            ? round($this->sodium / 1000, 1) . 'g'
            : $this->sodium . 'mg';
    }

    /**
     * Format water value with unit
     */
    public function getWaterFormattedAttribute()
    {
        if (!$this->water) {
            return 'N/A';
        }
        
        return $this->water . 'L';
    }
}
