<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\NutritionRestriction;
use App\Models\NutritionPlan;

/**
 * NutritionRestriction Factory
 * 
 * Generates realistic dietary restrictions, preferences, and allergen data
 * with proper combinations and realistic scenarios for comprehensive testing
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NutritionRestriction>
 * @package Database\Factories
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionRestrictionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NutritionRestriction::class;

    /**
     * Common dietary restriction combinations
     *
     * @var array
     */
    private array $restrictionCombinations = [
        'vegetarian_combo' => [
            'vegetarian' => true,
            'gluten_free' => false,
            'dairy_free' => false,
            'notes' => 'Vegetarian diet with flexibility for dairy and gluten.'
        ],
        'vegan_combo' => [
            'vegan' => true,
            'vegetarian' => true,
            'dairy_free' => true,
            'egg_free' => true,
            'notes' => 'Complete plant-based diet excluding all animal products.'
        ],
        'keto_combo' => [
            'keto' => true,
            'low_carb' => true,
            'high_protein' => true,
            'notes' => 'Ketogenic diet focusing on high fat, moderate protein, very low carb.'
        ],
        'paleo_combo' => [
            'paleo' => true,
            'gluten_free' => true,
            'dairy_free' => true,
            'notes' => 'Paleolithic diet excluding grains, legumes, and processed foods.'
        ],
        'celiac_combo' => [
            'gluten_free' => true,
            'notes' => 'Strict gluten-free diet required for celiac disease management.'
        ],
        'lactose_intolerant' => [
            'dairy_free' => true,
            'notes' => 'Lactose intolerance requiring dairy-free alternatives.'
        ],
        'diabetic_combo' => [
            'diabetic_friendly' => true,
            'low_sugar' => true,
            'notes' => 'Diabetic-friendly meal plan with controlled sugar and carbohydrate intake.'
        ],
        'heart_healthy_combo' => [
            'heart_healthy' => true,
            'low_sodium' => true,
            'mediterranean' => true,
            'notes' => 'Heart-healthy diet emphasizing omega-3 fatty acids and low sodium.'
        ],
        'allergy_combo' => [
            'nut_free' => true,
            'shellfish_free' => true,
            'notes' => 'Multiple food allergies requiring careful ingredient monitoring.'
        ]
    ];

    /**
     * Custom restriction examples
     *
     * @var array
     */
    private array $customRestrictions = [
        ['No red meat', 'Prefers white meat and fish only'],
        ['No spicy food', 'Sensitive to hot spices and peppers'],
        ['No raw foods', 'Prefers all foods cooked thoroughly'],
        ['No artificial sweeteners', 'Natural sweeteners only'],
        ['No processed foods', 'Whole foods approach preferred'],
        ['No nightshades', 'Avoiding tomatoes, potatoes, peppers, eggplant'],
        ['No citrus fruits', 'Citrus allergy or sensitivity'],
        ['No fermented foods', 'Digestive sensitivity to fermented products'],
        ['No caffeine', 'Avoiding all caffeinated beverages and foods'],
        ['No alcohol', 'Complete alcohol avoidance for health reasons']
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 60% chance of having some restrictions, 40% chance of no restrictions
        if ($this->faker->boolean(40)) {
            return $this->generateNoRestrictions();
        }
        
        // 30% chance of using a predefined combination
        if ($this->faker->boolean(30)) {
            return $this->generateFromCombination();
        }
        
        // Otherwise generate random restrictions
        return $this->generateRandomRestrictions();
    }

    /**
     * Generate a profile with no dietary restrictions
     *
     * @return array
     */
    private function generateNoRestrictions(): array
    {
        return [
            'plan_id' => NutritionPlan::factory(),
            'vegetarian' => false,
            'vegan' => false,
            'pescatarian' => false,
            'keto' => false,
            'paleo' => false,
            'mediterranean' => false,
            'low_carb' => false,
            'low_fat' => false,
            'high_protein' => false,
            'gluten_free' => false,
            'dairy_free' => false,
            'nut_free' => false,
            'soy_free' => false,
            'egg_free' => false,
            'shellfish_free' => false,
            'fish_free' => false,
            'sesame_free' => false,
            'diabetic_friendly' => false,
            'heart_healthy' => false,
            'low_sodium' => false,
            'low_sugar' => false,
            'custom_restrictions' => null,
            'notes' => 'No specific dietary restrictions or preferences.'
        ];
    }

    /**
     * Generate restrictions from a predefined combination
     *
     * @return array
     */
    private function generateFromCombination(): array
    {
        $combination = $this->faker->randomElement($this->restrictionCombinations);
        
        $base = $this->generateNoRestrictions();
        
        // Apply the combination
        foreach ($combination as $key => $value) {
            if ($key !== 'notes') {
                $base[$key] = $value;
            }
        }
        
        // Add custom restrictions occasionally
        if ($this->faker->boolean(30)) {
            $base['custom_restrictions'] = [$this->faker->randomElement($this->customRestrictions)];
        }
        
        $base['notes'] = $combination['notes'];
        
        return $base;
    }

    /**
     * Generate random dietary restrictions
     *
     * @return array
     */
    private function generateRandomRestrictions(): array
    {
        $base = $this->generateNoRestrictions();
        
        // Dietary preferences (mutually exclusive in some cases)
        $dietaryChoice = $this->faker->randomElement(['none', 'vegetarian', 'vegan', 'pescatarian', 'keto', 'paleo', 'mediterranean']);
        
        switch ($dietaryChoice) {
            case 'vegetarian':
                $base['vegetarian'] = true;
                break;
            case 'vegan':
                $base['vegan'] = true;
                $base['vegetarian'] = true;
                $base['dairy_free'] = true;
                $base['egg_free'] = true;
                break;
            case 'pescatarian':
                $base['pescatarian'] = true;
                break;
            case 'keto':
                $base['keto'] = true;
                $base['low_carb'] = true;
                break;
            case 'paleo':
                $base['paleo'] = true;
                $base['gluten_free'] = true;
                $base['dairy_free'] = $this->faker->boolean(70);
                break;
            case 'mediterranean':
                $base['mediterranean'] = true;
                $base['heart_healthy'] = $this->faker->boolean(60);
                break;
        }
        
        // Additional dietary approaches
        if ($dietaryChoice !== 'keto') {
            $base['low_carb'] = $this->faker->boolean(15);
        }
        $base['low_fat'] = $this->faker->boolean(10);
        $base['high_protein'] = $this->faker->boolean(20);
        
        // Allergens and intolerances (independent of dietary choices)
        if ($dietaryChoice !== 'paleo') {
            $base['gluten_free'] = $this->faker->boolean(15);
        }
        if ($dietaryChoice !== 'vegan' && $dietaryChoice !== 'paleo') {
            $base['dairy_free'] = $this->faker->boolean(12);
        }
        
        $base['nut_free'] = $this->faker->boolean(8);
        $base['soy_free'] = $this->faker->boolean(6);
        
        if ($dietaryChoice !== 'vegan') {
            $base['egg_free'] = $this->faker->boolean(5);
        }
        
        $base['shellfish_free'] = $this->faker->boolean(7);
        $base['fish_free'] = $this->faker->boolean(4);
        $base['sesame_free'] = $this->faker->boolean(3);
        
        // Medical restrictions
        $base['diabetic_friendly'] = $this->faker->boolean(10);
        if ($base['diabetic_friendly']) {
            $base['low_sugar'] = true;
        } else {
            $base['low_sugar'] = $this->faker->boolean(8);
        }
        
        $base['heart_healthy'] = $this->faker->boolean(12);
        if ($base['heart_healthy']) {
            $base['low_sodium'] = $this->faker->boolean(80);
        } else {
            $base['low_sodium'] = $this->faker->boolean(10);
        }
        
        // Custom restrictions
        if ($this->faker->boolean(25)) {
            $numCustom = $this->faker->numberBetween(1, 3);
            $base['custom_restrictions'] = $this->faker->randomElements($this->customRestrictions, $numCustom);
        }
        
        // Generate appropriate notes
        $base['notes'] = $this->generateNotes($base, $dietaryChoice);
        
        return $base;
    }

    /**
     * Generate appropriate notes based on restrictions
     *
     * @param array $restrictions
     * @param string $dietaryChoice
     * @return string
     */
    private function generateNotes(array $restrictions, string $dietaryChoice): string
    {
        $notes = [];
        
        if ($dietaryChoice !== 'none') {
            $notes[] = ucfirst($dietaryChoice) . ' dietary approach.';
        }
        
        $allergens = [];
        if ($restrictions['nut_free']) $allergens[] = 'nuts';
        if ($restrictions['shellfish_free']) $allergens[] = 'shellfish';
        if ($restrictions['egg_free']) $allergens[] = 'eggs';
        if ($restrictions['fish_free']) $allergens[] = 'fish';
        if ($restrictions['sesame_free']) $allergens[] = 'sesame';
        
        if (!empty($allergens)) {
            $notes[] = 'Allergic to: ' . implode(', ', $allergens) . '.';
        }
        
        $intolerances = [];
        if ($restrictions['gluten_free']) $intolerances[] = 'gluten intolerance';
        if ($restrictions['dairy_free'] && $dietaryChoice !== 'vegan') $intolerances[] = 'lactose intolerance';
        if ($restrictions['soy_free']) $intolerances[] = 'soy sensitivity';
        
        if (!empty($intolerances)) {
            $notes[] = 'Has: ' . implode(', ', $intolerances) . '.';
        }
        
        if ($restrictions['diabetic_friendly']) {
            $notes[] = 'Requires diabetic-friendly meal planning.';
        }
        
        if ($restrictions['heart_healthy']) {
            $notes[] = 'Following heart-healthy dietary guidelines.';
        }
        
        if (empty($notes)) {
            $notes[] = 'Custom dietary preferences and restrictions apply.';
        }
        
        $notes[] = 'Please review all ingredients carefully.';
        
        return implode(' ', $notes);
    }

    /**
     * Create a vegetarian restriction profile
     *
     * @return static
     */
    public function vegetarian(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge(
                $this->generateNoRestrictions(),
                [
                    'vegetarian' => true,
                    'notes' => 'Vegetarian diet - no meat, poultry, or fish.'
                ]
            );
        });
    }

    /**
     * Create a vegan restriction profile
     *
     * @return static
     */
    public function vegan(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge(
                $this->generateNoRestrictions(),
                [
                    'vegan' => true,
                    'vegetarian' => true,
                    'dairy_free' => true,
                    'egg_free' => true,
                    'notes' => 'Vegan diet - no animal products whatsoever.'
                ]
            );
        });
    }

    /**
     * Create a keto restriction profile
     *
     * @return static
     */
    public function keto(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge(
                $this->generateNoRestrictions(),
                [
                    'keto' => true,
                    'low_carb' => true,
                    'low_sugar' => true,
                    'notes' => 'Ketogenic diet - very low carb, high fat approach.'
                ]
            );
        });
    }

    /**
     * Create a gluten-free restriction profile
     *
     * @return static
     */
    public function glutenFree(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge(
                $this->generateNoRestrictions(),
                [
                    'gluten_free' => true,
                    'notes' => 'Gluten-free diet required for celiac disease or gluten sensitivity.'
                ]
            );
        });
    }

    /**
     * Create multiple allergy restriction profile
     *
     * @return static
     */
    public function multipleAllergies(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge(
                $this->generateNoRestrictions(),
                [
                    'nut_free' => true,
                    'shellfish_free' => true,
                    'egg_free' => true,
                    'dairy_free' => true,
                    'notes' => 'Multiple food allergies - requires careful ingredient monitoring and preparation.'
                ]
            );
        });
    }

    /**
     * Create restrictions for a specific plan
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
     * Create no restrictions profile
     *
     * @return static
     */
    public function noRestrictions(): static
    {
        return $this->state(function (array $attributes) {
            return $this->generateNoRestrictions();
        });
    }
}