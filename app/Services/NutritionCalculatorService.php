<?php

namespace App\Services;

use InvalidArgumentException;
use App\Support\UnitConverter;

/**
 * NutritionCalculatorService
 * 
 * Calculates nutrition recommendations based on user data and goals
 * Uses BMR (Basal Metabolic Rate) and TDEE (Total Daily Energy Expenditure) formulas
 * Provides macro distribution based on goal types
 * 
 * @package App\Services
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionCalculatorService
{
    /**
     * Activity level multipliers for TDEE calculation
     */
    const ACTIVITY_LEVELS = [
        'sedentary' => 1.2,        // Little to no exercise
        'lightly_active' => 1.375, // Light exercise 1-3 days/week
        'moderately_active' => 1.55, // Moderate exercise 3-5 days/week
        'very_active' => 1.725,    // Hard exercise 6-7 days/week
        'extremely_active' => 1.9  // Very hard exercise, physical job
    ];

    /**
     * Macro distribution percentages by goal type
     * Format: [protein%, carbs%, fats%]
     */
    const MACRO_DISTRIBUTIONS = [
        'weight_loss' => [35, 30, 35],      // High protein, moderate carbs, moderate fats
        'weight_gain' => [25, 45, 30],      // Moderate protein, high carbs, moderate fats
        'muscle_gain' => [30, 40, 30],      // High protein, moderate carbs, moderate fats
        'maintenance' => [25, 45, 30],      // Balanced distribution
        'wellness' => [20, 50, 30],         // Balanced for general health
        'athletic_performance' => [25, 50, 25], // High carbs for performance
        'general_health' => [20, 50, 30]    // General health maintenance
    ];

    /**
     * Calorie adjustments by goal type (percentage of TDEE)
     */
    const CALORIE_ADJUSTMENTS = [
        'weight_loss' => -0.20,        // 20% deficit
        'weight_gain' => 0.15,         // 15% surplus
        'muscle_gain' => 0.10,         // 10% surplus
        'maintenance' => 0.00,         // No adjustment
        'wellness' => 0.00,            // No adjustment
        'athletic_performance' => 0.05, // 5% surplus
        'general_health' => 0.00       // No adjustment
    ];

    /**
     * Calculate BMR using Mifflin-St Jeor Equation
     * 
     * @param float $weight Weight in kg
     * @param float $height Height in cm
     * @param int $age Age in years
     * @param string $gender 'male' or 'female'
     * @return float BMR in calories
     * @throws InvalidArgumentException
     */
    public function calculateBMR(float $weightKg, float $height, int $age, string $gender): float
    {
        $this->validateInputsLbs($weightKg * 2.20462, $height, $age, $gender);

        // Mifflin-St Jeor Equation
        if ($gender === 'male') {
            $bmr = (10 * $weightKg) + (6.25 * $height) - (5 * $age) + 5;
        } else {
            $bmr = (10 * $weightKg) + (6.25 * $height) - (5 * $age) - 161;
        }

        return round($bmr, 2);
    }

    /**
     * Calculate TDEE (Total Daily Energy Expenditure)
     * 
     * @param float $bmr Basal Metabolic Rate
     * @param string $activityLevel Activity level key
     * @return float TDEE in calories
     * @throws InvalidArgumentException
     */
    public function calculateTDEE(float $bmr, string $activityLevel): float
    {
        if (!array_key_exists($activityLevel, self::ACTIVITY_LEVELS)) {
            throw new InvalidArgumentException("Invalid activity level: {$activityLevel}");
        }

        $tdee = $bmr * self::ACTIVITY_LEVELS[$activityLevel];
        return round($tdee, 2);
    }

    /**
     * Calculate target calories based on goal type
     * 
     * @param float $tdee Total Daily Energy Expenditure
     * @param string $goalType Goal type key
     * @return float Target calories
     * @throws InvalidArgumentException
     */
    public function calculateTargetCalories(float $tdee, string $goalType): float
    {
        if (!array_key_exists($goalType, self::CALORIE_ADJUSTMENTS)) {
            throw new InvalidArgumentException("Invalid goal type: {$goalType}");
        }

        $adjustment = self::CALORIE_ADJUSTMENTS[$goalType];
        $targetCalories = $tdee * (1 + $adjustment);
        
        return round($targetCalories, 0);
    }

    /**
     * Calculate macro distribution (protein, carbs, fats in grams)
     * 
     * @param float $targetCalories Target daily calories
     * @param string $goalType Goal type key
     * @return array ['protein' => grams, 'carbs' => grams, 'fats' => grams]
     * @throws InvalidArgumentException
     */
    public function calculateMacros(float $targetCalories, string $goalType): array
    {
        if (!array_key_exists($goalType, self::MACRO_DISTRIBUTIONS)) {
            throw new InvalidArgumentException("Invalid goal type: {$goalType}");
        }

        $distribution = self::MACRO_DISTRIBUTIONS[$goalType];
        
        // Calculate calories for each macro
        $proteinCalories = $targetCalories * ($distribution[0] / 100);
        $carbsCalories = $targetCalories * ($distribution[1] / 100);
        $fatsCalories = $targetCalories * ($distribution[2] / 100);
        
        // Convert to grams (protein: 4 cal/g, carbs: 4 cal/g, fats: 9 cal/g)
        $protein = round($proteinCalories / 4, 1);
        $carbs = round($carbsCalories / 4, 1);
        $fats = round($fatsCalories / 9, 1);
        
        return [
            'protein' => $protein,
            'carbs' => $carbs,
            'fats' => $fats,
            'protein_percentage' => $distribution[0],
            'carbs_percentage' => $distribution[1],
            'fats_percentage' => $distribution[2]
        ];
    }

    /**
     * Complete nutrition calculation
     * 
     * @param array $userData User data containing weight, height, age, gender, activity_level, goal_type
     * @return array Complete nutrition recommendations
     * @throws InvalidArgumentException
     */
    public function calculateNutrition(array $userData): array
    {
        // Extract and validate user data
        $weightLbs = (float) ($userData['weight'] ?? 0);
        $height = (float) ($userData['height'] ?? 0);
        $age = (int) ($userData['age'] ?? 0);
        $gender = strtolower($userData['gender'] ?? '');
        $activityLevel = $userData['activity_level'] ?? '';
        $goalType = $userData['goal_type'] ?? '';

        $weightKg = UnitConverter::lbsToKg($weightLbs);
        $bmr = $this->calculateBMR($weightKg, $height, $age, $gender);
        
        // Calculate TDEE
        $tdee = $this->calculateTDEE($bmr, $activityLevel);
        
        // Calculate target calories
        $targetCalories = $this->calculateTargetCalories($tdee, $goalType);
        
        // Calculate macros
        $macros = $this->calculateMacros($targetCalories, $goalType);
        
        $weeklyWeightChangeKg = $this->calculateWeeklyWeightChangeKg($tdee, $targetCalories);
        $weeklyWeightChangeLbs = UnitConverter::kgToLbs($weeklyWeightChangeKg);
        
        return [
            'user_data' => [
                'weight' => $weightLbs,
                'height' => $height,
                'age' => $age,
                'gender' => $gender,
                'activity_level' => $activityLevel,
                'goal_type' => $goalType
            ],
            'calculations' => [
                'bmr' => $bmr,
                'tdee' => $tdee,
                'target_calories' => $targetCalories,
                'calorie_deficit_surplus' => $targetCalories - $tdee,
                'weekly_weight_change_lbs' => $weeklyWeightChangeLbs
            ],
            'recommendations' => [
                'target_calories' => $targetCalories,
                'protein' => $macros['protein'],
                'carbs' => $macros['carbs'],
                'fats' => $macros['fats'],
                'macro_distribution' => [
                    'protein_percentage' => $macros['protein_percentage'],
                    'carbs_percentage' => $macros['carbs_percentage'],
                    'fats_percentage' => $macros['fats_percentage']
                ]
            ],
            'meta' => [
                'activity_level_display' => $this->getActivityLevelDisplay($activityLevel),
                'goal_type_display' => $this->getGoalTypeDisplay($goalType),
                'calculation_date' => now()->toDateTimeString(),
                'formula_used' => 'Mifflin-St Jeor Equation'
            ]
        ];
    }

    /**
     * Calculate estimated weekly weight change
     * 
     * @param float $tdee Total Daily Energy Expenditure
     * @param float $targetCalories Target daily calories
     * @return float Weekly weight change in kg (positive = gain, negative = loss)
     */
    private function calculateWeeklyWeightChangeKg(float $tdee, float $targetCalories): float
    {
        $dailyCalorieDifference = $targetCalories - $tdee;
        $weeklyCalorieDifference = $dailyCalorieDifference * 7;
        
        // 1 kg of body weight ≈ 7700 calories
        $weeklyWeightChange = $weeklyCalorieDifference / 7700;
        
        return round($weeklyWeightChange, 2);
    }

    

    /**
     * Get available activity levels
     * 
     * @return array Activity levels with display names
     */
    public function getActivityLevels(): array
    {
        return [
            'sedentary' => [
                'value' => 'sedentary',
                'label' => 'Sedentary',
                'description' => 'Little to no exercise',
                'multiplier' => self::ACTIVITY_LEVELS['sedentary']
            ],
            'lightly_active' => [
                'value' => 'lightly_active',
                'label' => 'Lightly Active',
                'description' => 'Light exercise 1-3 days/week',
                'multiplier' => self::ACTIVITY_LEVELS['lightly_active']
            ],
            'moderately_active' => [
                'value' => 'moderately_active',
                'label' => 'Moderately Active',
                'description' => 'Moderate exercise 3-5 days/week',
                'multiplier' => self::ACTIVITY_LEVELS['moderately_active']
            ],
            'very_active' => [
                'value' => 'very_active',
                'label' => 'Very Active',
                'description' => 'Hard exercise 6-7 days/week',
                'multiplier' => self::ACTIVITY_LEVELS['very_active']
            ],
            'extremely_active' => [
                'value' => 'extremely_active',
                'label' => 'Extremely Active',
                'description' => 'Very hard exercise, physical job',
                'multiplier' => self::ACTIVITY_LEVELS['extremely_active']
            ]
        ];
    }

    /**
     * Get available goal types
     * 
     * @return array Goal types with display names and descriptions
     */
    public function getGoalTypes(): array
    {
        return [
            'weight_loss' => [
                'value' => 'weight_loss',
                'label' => 'Weight Loss',
                'description' => 'Lose weight through calorie deficit',
                'calorie_adjustment' => self::CALORIE_ADJUSTMENTS['weight_loss']
            ],
            'weight_gain' => [
                'value' => 'weight_gain',
                'label' => 'Weight Gain',
                'description' => 'Gain weight through calorie surplus',
                'calorie_adjustment' => self::CALORIE_ADJUSTMENTS['weight_gain']
            ],
            'muscle_gain' => [
                'value' => 'muscle_gain',
                'label' => 'Muscle Gain',
                'description' => 'Build muscle with optimal protein intake',
                'calorie_adjustment' => self::CALORIE_ADJUSTMENTS['muscle_gain']
            ],
            'maintenance' => [
                'value' => 'maintenance',
                'label' => 'Maintenance',
                'description' => 'Maintain current weight',
                'calorie_adjustment' => self::CALORIE_ADJUSTMENTS['maintenance']
            ],
            'wellness' => [
                'value' => 'wellness',
                'label' => 'Wellness',
                'description' => 'General health and wellness',
                'calorie_adjustment' => self::CALORIE_ADJUSTMENTS['wellness']
            ],
            'athletic_performance' => [
                'value' => 'athletic_performance',
                'label' => 'Athletic Performance',
                'description' => 'Optimize for athletic performance',
                'calorie_adjustment' => self::CALORIE_ADJUSTMENTS['athletic_performance']
            ]
        ];
    }

    /**
     * Validate input parameters
     * 
     * @param float $weight
     * @param float $height
     * @param int $age
     * @param string $gender
     * @throws InvalidArgumentException
     */
    private function validateInputsLbs(float $weightLbs, float $height, int $age, string $gender): void
    {
        if ($weightLbs <= 0 || $weightLbs > 1100) {
            throw new InvalidArgumentException('Weight must be between 1 and 1100 lbs');
        }

        if ($height <= 0 || $height > 300) {
            throw new InvalidArgumentException('Height must be between 1 and 300 cm');
        }

        if ($age <= 0 || $age > 120) {
            throw new InvalidArgumentException('Age must be between 1 and 120 years');
        }

        if (!in_array($gender, ['male', 'female'])) {
            throw new InvalidArgumentException('Gender must be either "male" or "female"');
        }
    }

    /**
     * Get activity level display name
     * 
     * @param string $activityLevel
     * @return string
     */
    private function getActivityLevelDisplay(string $activityLevel): string
    {
        $levels = $this->getActivityLevels();
        return $levels[$activityLevel]['label'] ?? ucfirst(str_replace('_', ' ', $activityLevel));
    }

    /**
     * Get goal type display name
     * 
     * @param string $goalType
     * @return string
     */
    private function getGoalTypeDisplay(string $goalType): string
    {
        $goals = $this->getGoalTypes();
        return $goals[$goalType]['label'] ?? ucfirst(str_replace('_', ' ', $goalType));
    }
}