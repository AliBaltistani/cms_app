<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NutritionRestriction Model
 * 
 * Manages dietary restrictions and preferences for nutrition plans
 * Handles allergens, dietary preferences, and medical restrictions
 * 
 * @package App\Models
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionRestriction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'nutrition_restrictions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'vegetarian',
        'vegan',
        'pescatarian',
        'keto',
        'paleo',
        'mediterranean',
        'low_carb',
        'low_fat',
        'high_protein',
        'gluten_free',
        'dairy_free',
        'nut_free',
        'soy_free',
        'egg_free',
        'shellfish_free',
        'fish_free',
        'sesame_free',
        'diabetic_friendly',
        'heart_healthy',
        'low_sodium',
        'low_sugar',
        'custom_restrictions',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'vegetarian' => 'boolean',
        'vegan' => 'boolean',
        'pescatarian' => 'boolean',
        'keto' => 'boolean',
        'paleo' => 'boolean',
        'mediterranean' => 'boolean',
        'low_carb' => 'boolean',
        'low_fat' => 'boolean',
        'high_protein' => 'boolean',
        'gluten_free' => 'boolean',
        'dairy_free' => 'boolean',
        'nut_free' => 'boolean',
        'soy_free' => 'boolean',
        'egg_free' => 'boolean',
        'shellfish_free' => 'boolean',
        'fish_free' => 'boolean',
        'sesame_free' => 'boolean',
        'diabetic_friendly' => 'boolean',
        'heart_healthy' => 'boolean',
        'low_sodium' => 'boolean',
        'low_sugar' => 'boolean',
        'custom_restrictions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the nutrition plan this restriction belongs to
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(NutritionPlan::class, 'plan_id');
    }

    /**
     * Get all dietary preferences
     */
    public function getDietaryPreferencesAttribute()
    {
        $preferences = [];
        
        $dietaryFields = [
            'vegetarian' => 'Vegetarian',
            'vegan' => 'Vegan',
            'pescatarian' => 'Pescatarian',
            'keto' => 'Ketogenic',
            'paleo' => 'Paleo',
            'mediterranean' => 'Mediterranean',
            'low_carb' => 'Low Carb',
            'low_fat' => 'Low Fat',
            'high_protein' => 'High Protein'
        ];
        
        foreach ($dietaryFields as $field => $label) {
            if ($this->$field) {
                $preferences[] = $label;
            }
        }
        
        return $preferences;
    }

    /**
     * Get all allergens and intolerances
     */
    public function getAllergensAttribute()
    {
        $allergens = [];
        
        $allergenFields = [
            'gluten_free' => 'Gluten-Free',
            'dairy_free' => 'Dairy-Free',
            'nut_free' => 'Nut-Free',
            'soy_free' => 'Soy-Free',
            'egg_free' => 'Egg-Free',
            'shellfish_free' => 'Shellfish-Free',
            'fish_free' => 'Fish-Free',
            'sesame_free' => 'Sesame-Free'
        ];
        
        foreach ($allergenFields as $field => $label) {
            if ($this->$field) {
                $allergens[] = $label;
            }
        }
        
        return $allergens;
    }

    /**
     * Get all medical restrictions
     */
    public function getMedicalRestrictionsAttribute()
    {
        $restrictions = [];
        
        $medicalFields = [
            'diabetic_friendly' => 'Diabetic Friendly',
            'heart_healthy' => 'Heart Healthy',
            'low_sodium' => 'Low Sodium',
            'low_sugar' => 'Low Sugar'
        ];
        
        foreach ($medicalFields as $field => $label) {
            if ($this->$field) {
                $restrictions[] = $label;
            }
        }
        
        return $restrictions;
    }

    /**
     * Get all active restrictions as a combined array
     */
    public function getAllRestrictionsAttribute()
    {
        return array_merge(
            $this->dietary_preferences,
            $this->allergens,
            $this->medical_restrictions,
            $this->custom_restrictions ?? []
        );
    }

    /**
     * Check if has any dietary preferences
     */
    public function getHasDietaryPreferencesAttribute()
    {
        return !empty($this->dietary_preferences);
    }

    /**
     * Check if has any allergens
     */
    public function getHasAllergensAttribute()
    {
        return !empty($this->allergens);
    }

    /**
     * Check if has any medical restrictions
     */
    public function getHasMedicalRestrictionsAttribute()
    {
        return !empty($this->medical_restrictions);
    }

    /**
     * Check if has any custom restrictions
     */
    public function getHasCustomRestrictionsAttribute()
    {
        return !empty($this->custom_restrictions);
    }

    /**
     * Get restrictions summary for display
     */
    public function getRestrictionsSummaryAttribute()
    {
        $allRestrictions = $this->all_restrictions;
        
        if (empty($allRestrictions)) {
            return 'No dietary restrictions';
        }
        
        if (count($allRestrictions) <= 3) {
            return implode(', ', $allRestrictions);
        }
        
        return implode(', ', array_slice($allRestrictions, 0, 3)) . ' and ' . (count($allRestrictions) - 3) . ' more';
    }

    /**
     * Check if a food item is compatible with restrictions
     */
    public function isCompatibleWith(array $foodAttributes): bool
    {
        // Check dietary preferences
        if ($this->vegan && !($foodAttributes['vegan'] ?? false)) {
            return false;
        }
        
        if ($this->vegetarian && !($foodAttributes['vegetarian'] ?? false)) {
            return false;
        }
        
        // Check allergens
        $allergenChecks = [
            'gluten_free' => 'contains_gluten',
            'dairy_free' => 'contains_dairy',
            'nut_free' => 'contains_nuts',
            'soy_free' => 'contains_soy',
            'egg_free' => 'contains_eggs',
            'shellfish_free' => 'contains_shellfish',
            'fish_free' => 'contains_fish',
            'sesame_free' => 'contains_sesame'
        ];
        
        foreach ($allergenChecks as $restriction => $attribute) {
            if ($this->$restriction && ($foodAttributes[$attribute] ?? false)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get restriction badges for UI display
     */
    public function getRestrictionBadgesAttribute()
    {
        $badges = [];
        $allRestrictions = $this->all_restrictions;
        
        foreach ($allRestrictions as $restriction) {
            $badges[] = [
                'text' => $restriction,
                'class' => $this->getBadgeClass($restriction)
            ];
        }
        
        return $badges;
    }

    /**
     * Get appropriate CSS class for restriction badge
     */
    private function getBadgeClass(string $restriction): string
    {
        $classMap = [
            'Vegan' => 'bg-success-transparent',
            'Vegetarian' => 'bg-success-transparent',
            'Gluten-Free' => 'bg-warning-transparent',
            'Dairy-Free' => 'bg-info-transparent',
            'Diabetic Friendly' => 'bg-danger-transparent',
            'Heart Healthy' => 'bg-primary-transparent',
        ];
        
        return $classMap[$restriction] ?? 'bg-secondary-transparent';
    }
}
