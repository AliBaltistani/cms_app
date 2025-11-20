<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\NutritionMacro;
use App\Models\NutritionPlan;
use App\Models\NutritionMeal;

/**
 * NutritionMacro Factory
 * 
 * Generates realistic macronutrient data for both daily targets and meal-specific
 * macros with proper nutritional balance and calculations
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NutritionMacro>
 * @package Database\Factories
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionMacroFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NutritionMacro::class;

    /**
     * Macro distribution templates based on different diet types
     *
     * @var array
     */
    private array $macroTemplates = [
        'balanced' => [
            'protein_percent' => [25, 35],
            'carbs_percent' => [40, 50],
            'fats_percent' => [20, 30]
        ],
        'high_protein' => [
            'protein_percent' => [35, 45],
            'carbs_percent' => [25, 35],
            'fats_percent' => [20, 30]
        ],
        'low_carb' => [
            'protein_percent' => [25, 35],
            'carbs_percent' => [10, 25],
            'fats_percent' => [40, 60]
        ],
        'keto' => [
            'protein_percent' => [20, 25],
            'carbs_percent' => [5, 10],
            'fats_percent' => [70, 80]
        ],
        'endurance' => [
            'protein_percent' => [15, 25],
            'carbs_percent' => [55, 65],
            'fats_percent' => [20, 25]
        ]
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $macroType = $this->faker->randomElement(['daily_target', 'meal_specific']);
        
        if ($macroType === 'daily_target') {
            return $this->generateDailyTarget();
        } else {
            return $this->generateMealSpecific();
        }
    }

    /**
     * Generate daily target macros
     *
     * @return array
     */
    private function generateDailyTarget(): array
    {
        // Base calories on typical daily requirements
        $totalCalories = $this->faker->numberBetween(1200, 3000);
        
        // Select a macro template
        $template = $this->faker->randomElement($this->macroTemplates);
        
        // Calculate macros based on percentages
        $proteinPercent = $this->faker->numberBetween($template['protein_percent'][0], $template['protein_percent'][1]);
        $carbsPercent = $this->faker->numberBetween($template['carbs_percent'][0], $template['carbs_percent'][1]);
        $fatsPercent = 100 - $proteinPercent - $carbsPercent;
        
        // Ensure fats percentage is within reasonable range
        $fatsPercent = max(15, min(70, $fatsPercent));
        
        // Calculate oz (protein: 4 cal/oz, carbs: 4 cal/oz, fats: 9 cal/oz)
        $proteinOz = round(($totalCalories * $proteinPercent / 100) / 4, 1);
        $carbsOz = round(($totalCalories * $carbsPercent / 100) / 4, 1);
        $fatsOz = round(($totalCalories * $fatsPercent / 100) / 9, 1);
        
        // Recalculate total calories based on actual oz
        $actualCalories = ($proteinOz * 4) + ($carbsOz * 4) + ($fatsOz * 9);

        return [
            'plan_id' => NutritionPlan::factory(),
            'protein' => $proteinOz,
            'carbs' => $carbsOz,
            'fats' => $fatsOz,
            'total_calories' => round($actualCalories, 1),
            'fiber' => $this->faker->randomFloat(1, 25, 50), // Daily fiber recommendation
            'sugar' => $this->faker->randomFloat(1, 20, 60), // Natural sugars from fruits/dairy
            'sodium' => $this->faker->randomFloat(1, 1500, 2300), // mg per day
            'water' => $this->faker->randomFloat(1, 2.0, 4.0), // liters per day
            'macro_type' => 'daily_target',
            'meal_id' => null,
            'notes' => $this->generateDailyNotes($proteinPercent, $carbsPercent, $fatsPercent),
        ];
    }

    /**
     * Generate meal-specific macros
     *
     * @return array
     */
    private function generateMealSpecific(): array
    {
        // Meal-specific calories are typically 200-800 per meal
        $mealCalories = $this->faker->numberBetween(200, 800);
        
        // Meal macros can vary more than daily targets
        $proteinPercent = $this->faker->numberBetween(15, 50);
        $carbsPercent = $this->faker->numberBetween(20, 60);
        $fatsPercent = 100 - $proteinPercent - $carbsPercent;
        
        // Ensure reasonable fat percentage
        $fatsPercent = max(10, min(60, $fatsPercent));
        
        // Calculate oz
        $proteinOz = round(($mealCalories * $proteinPercent / 100) / 4, 1);
        $carbsOz = round(($mealCalories * $carbsPercent / 100) / 4, 1);
        $fatsOz = round(($mealCalories * $fatsPercent / 100) / 9, 1);
        
        // Recalculate actual calories
        $actualCalories = ($proteinOz * 4) + ($carbsOz * 4) + ($fatsOz * 9);

        return [
            'plan_id' => NutritionPlan::factory(),
            'protein' => $proteinOz,
            'carbs' => $carbsOz,
            'fats' => $fatsOz,
            'total_calories' => round($actualCalories, 1),
            'fiber' => $this->faker->randomFloat(1, 2, 15), // Per meal fiber
            'sugar' => $this->faker->randomFloat(1, 1, 20), // Per meal sugar
            'sodium' => $this->faker->randomFloat(1, 100, 800), // mg per meal
            'water' => $this->faker->randomFloat(1, 0.2, 0.8), // liters per meal
            'macro_type' => 'meal_specific',
            'meal_id' => NutritionMeal::factory(),
            'notes' => $this->generateMealNotes(),
        ];
    }

    /**
     * Generate notes for daily target macros
     *
     * @param int $proteinPercent
     * @param int $carbsPercent
     * @param int $fatsPercent
     * @return string
     */
    private function generateDailyNotes(int $proteinPercent, int $carbsPercent, int $fatsPercent): string
    {
        $notes = [];
        
        if ($proteinPercent >= 35) {
            $notes[] = 'High-protein diet suitable for muscle building and recovery.';
        } elseif ($proteinPercent <= 20) {
            $notes[] = 'Lower protein intake - ensure adequate amino acid variety.';
        }
        
        if ($carbsPercent <= 15) {
            $notes[] = 'Low-carb approach - monitor energy levels and adjust as needed.';
        } elseif ($carbsPercent >= 55) {
            $notes[] = 'Higher carb intake suitable for endurance activities.';
        }
        
        if ($fatsPercent >= 50) {
            $notes[] = 'High-fat diet - focus on healthy fats from nuts, oils, and fish.';
        }
        
        $notes[] = 'Adjust portions based on activity level and progress.';
        $notes[] = 'Stay hydrated and monitor how you feel with these targets.';
        
        $maxNotes = min(3, count($notes));
        return implode(' ', $this->faker->randomElements($notes, $this->faker->numberBetween(1, $maxNotes)));
    }

    /**
     * Generate notes for meal-specific macros
     *
     * @return string
     */
    private function generateMealNotes(): string
    {
        $mealNotes = [
            'Adjust portion sizes based on hunger and activity level.',
            'Can be prepared in advance for meal prep convenience.',
            'Substitute ingredients based on dietary preferences.',
            'Add herbs and spices for flavor without extra calories.',
            'Pair with adequate hydration throughout the day.',
            'Consider timing around workouts for optimal performance.',
            'Monitor how this meal affects your energy levels.',
            'Can be modified for different dietary restrictions.'
        ];
        
        return $this->faker->randomElement($mealNotes);
    }

    /**
     * Create daily target macros
     *
     * @return static
     */
    public function dailyTarget(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge(
                $this->generateDailyTarget(),
                ['meal_id' => null]
            );
        });
    }

    /**
     * Create meal-specific macros
     *
     * @return static
     */
    public function mealSpecific(): static
    {
        return $this->state(function (array $attributes) {
            return $this->generateMealSpecific();
        });
    }

    /**
     * Create high-protein macros
     *
     * @return static
     */
    public function highProtein(): static
    {
        return $this->state(function (array $attributes) {
            $template = $this->macroTemplates['high_protein'];
            $totalCalories = $attributes['total_calories'] ?? 2000;
            
            $proteinPercent = $this->faker->numberBetween($template['protein_percent'][0], $template['protein_percent'][1]);
            $carbsPercent = $this->faker->numberBetween($template['carbs_percent'][0], $template['carbs_percent'][1]);
            $fatsPercent = 100 - $proteinPercent - $carbsPercent;
            
            return [
                'protein' => round(($totalCalories * $proteinPercent / 100) / 4, 1),
                'carbs' => round(($totalCalories * $carbsPercent / 100) / 4, 1),
                'fats' => round(($totalCalories * $fatsPercent / 100) / 9, 1),
                'notes' => 'High-protein macro distribution for muscle building and recovery.'
            ];
        });
    }

    /**
     * Create keto-friendly macros
     *
     * @return static
     */
    public function keto(): static
    {
        return $this->state(function (array $attributes) {
            $template = $this->macroTemplates['keto'];
            $totalCalories = $attributes['total_calories'] ?? 2000;
            
            $proteinPercent = $this->faker->numberBetween($template['protein_percent'][0], $template['protein_percent'][1]);
            $carbsPercent = $this->faker->numberBetween($template['carbs_percent'][0], $template['carbs_percent'][1]);
            $fatsPercent = 100 - $proteinPercent - $carbsPercent;
            
            return [
                'protein' => round(($totalCalories * $proteinPercent / 100) / 4, 1),
                'carbs' => round(($totalCalories * $carbsPercent / 100) / 4, 1),
                'fats' => round(($totalCalories * $fatsPercent / 100) / 9, 1),
                'notes' => 'Ketogenic macro distribution - very low carb, high fat approach.'
            ];
        });
    }

    /**
     * Create macros for a specific plan
     *
     * @param int $planId
     * @return static
     */
    public function forPlan(int $planId): static
    {
        return $this->state(function (array $attributes) use ($planId) {
            return [
                'plan_id' => $planId,
            ];
        });
    }

    /**
     * Create macros for a specific meal
     *
     * @param int $mealId
     * @return static
     */
    public function forMeal(int $mealId): static
    {
        return $this->state(function (array $attributes) use ($mealId) {
            return array_merge(
                $this->generateMealSpecific(),
                [
                    'meal_id' => $mealId,
                    'macro_type' => 'meal_specific'
                ]
            );
        });
    }
}