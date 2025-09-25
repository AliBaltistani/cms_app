<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NutritionRecommendation Model
 * 
 * Manages macronutrient recommendations for nutrition plans
 * Allows trainers to set specific macro targets for clients
 * 
 * @package App\Models
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionRecommendation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'nutrition_recommendations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'target_calories',
        'protein',
        'carbs',
        'fats'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'target_calories' => 'decimal:2',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fats' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the nutrition plan this recommendation belongs to
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(NutritionPlan::class, 'plan_id');
    }

    /**
     * Calculate total macronutrient calories
     * 
     * @return float
     */
    public function getTotalMacroCaloriesAttribute(): float
    {
        return ($this->protein * 4) + ($this->carbs * 4) + ($this->fats * 9);
    }

    /**
     * Get macro distribution percentages
     * 
     * @return array
     */
    public function getMacroDistributionAttribute(): array
    {
        $totalCalories = $this->target_calories > 0 ? $this->target_calories : $this->total_macro_calories;
        
        if ($totalCalories <= 0) {
            return [
                'protein_percentage' => 0,
                'carbs_percentage' => 0,
                'fats_percentage' => 0
            ];
        }

        return [
            'protein_percentage' => round(($this->protein * 4 / $totalCalories) * 100, 1),
            'carbs_percentage' => round(($this->carbs * 4 / $totalCalories) * 100, 1),
            'fats_percentage' => round(($this->fats * 9 / $totalCalories) * 100, 1)
        ];
    }

    /**
     * Scope to get recommendations by plan
     */
    public function scopeByPlan($query, $planId)
    {
        return $query->where('plan_id', $planId);
    }
}