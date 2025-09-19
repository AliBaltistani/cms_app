<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\NutritionMeal;
use App\Models\NutritionPlan;

/**
 * NutritionMeal Factory
 * 
 * Generates realistic meal data with proper nutritional information,
 * ingredients, and cooking instructions for comprehensive testing
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NutritionMeal>
 * @package Database\Factories
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionMealFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NutritionMeal::class;

    /**
     * Meal data organized by type for realistic generation
     *
     * @var array
     */
    private array $mealData = [
        'breakfast' => [
            'titles' => [
                'Protein Pancakes with Berries',
                'Overnight Oats with Almonds',
                'Greek Yogurt Parfait',
                'Avocado Toast with Eggs',
                'Smoothie Bowl with Granola',
                'Scrambled Eggs with Spinach',
                'Chia Seed Pudding',
                'Quinoa Breakfast Bowl'
            ],
            'ingredients' => [
                '2 eggs, 1/2 cup oats, 1 banana, 1/4 cup blueberries, 1 tbsp honey',
                '1/2 cup rolled oats, 1/2 cup almond milk, 1 tbsp chia seeds, 1/4 cup berries',
                '1 cup Greek yogurt, 1/4 cup granola, 1/2 cup mixed berries, 1 tbsp honey',
                '2 slices whole grain bread, 1 avocado, 2 eggs, salt, pepper, lemon juice',
                '1 frozen banana, 1/2 cup berries, 1/4 cup granola, 1 tbsp almond butter'
            ],
            'calories' => [300, 450],
            'protein' => [15, 35],
            'carbs' => [25, 55],
            'fats' => [8, 20]
        ],
        'lunch' => [
            'titles' => [
                'Grilled Chicken Salad',
                'Quinoa Buddha Bowl',
                'Turkey and Hummus Wrap',
                'Lentil Soup with Vegetables',
                'Salmon with Sweet Potato',
                'Mediterranean Chickpea Salad',
                'Beef Stir-fry with Brown Rice',
                'Tuna Poke Bowl'
            ],
            'ingredients' => [
                '150g grilled chicken breast, mixed greens, cherry tomatoes, cucumber, olive oil dressing',
                '1/2 cup cooked quinoa, roasted vegetables, chickpeas, tahini dressing',
                'Whole wheat tortilla, 100g turkey breast, 2 tbsp hummus, vegetables',
                '1 cup cooked lentils, mixed vegetables, vegetable broth, herbs and spices',
                '150g grilled salmon, 1 medium roasted sweet potato, steamed broccoli'
            ],
            'calories' => [400, 650],
            'protein' => [25, 45],
            'carbs' => [30, 60],
            'fats' => [12, 25]
        ],
        'dinner' => [
            'titles' => [
                'Baked Cod with Vegetables',
                'Chicken Curry with Rice',
                'Vegetarian Pasta Primavera',
                'Lean Beef with Quinoa',
                'Tofu Stir-fry with Noodles',
                'Grilled Shrimp Tacos',
                'Stuffed Bell Peppers',
                'Moroccan Chicken Tagine'
            ],
            'ingredients' => [
                '150g cod fillet, mixed roasted vegetables, olive oil, herbs',
                '150g chicken breast, coconut curry sauce, 1/2 cup brown rice, vegetables',
                'Whole wheat pasta, seasonal vegetables, olive oil, parmesan cheese',
                '120g lean beef, 1/2 cup quinoa, roasted vegetables, herbs',
                '150g firm tofu, rice noodles, mixed vegetables, soy sauce, ginger'
            ],
            'calories' => [450, 700],
            'protein' => [30, 50],
            'carbs' => [35, 65],
            'fats' => [15, 28]
        ],
        'snack' => [
            'titles' => [
                'Apple with Almond Butter',
                'Greek Yogurt with Nuts',
                'Protein Smoothie',
                'Hummus with Vegetables',
                'Trail Mix',
                'Cottage Cheese Bowl',
                'Energy Balls',
                'Avocado Toast'
            ],
            'ingredients' => [
                '1 medium apple, 2 tbsp almond butter',
                '1/2 cup Greek yogurt, 1 oz mixed nuts',
                '1 scoop protein powder, 1 cup almond milk, 1/2 banana',
                '1/4 cup hummus, sliced vegetables (carrots, bell peppers, cucumber)',
                '1 oz mixed nuts, dried fruits, dark chocolate chips'
            ],
            'calories' => [150, 300],
            'protein' => [8, 20],
            'carbs' => [15, 25],
            'fats' => [8, 15]
        ]
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mealTypes = ['breakfast', 'lunch', 'dinner', 'snack', 'pre_workout', 'post_workout'];
        $selectedType = $this->faker->randomElement($mealTypes);
        
        // Use predefined data for common meal types, generate for workout meals
        if (isset($this->mealData[$selectedType])) {
            $data = $this->mealData[$selectedType];
            $title = $this->faker->randomElement($data['titles']);
            $ingredients = $this->faker->randomElement($data['ingredients']);
            $calories = $this->faker->numberBetween($data['calories'][0], $data['calories'][1]);
            $protein = $this->faker->numberBetween($data['protein'][0], $data['protein'][1]);
            $carbs = $this->faker->numberBetween($data['carbs'][0], $data['carbs'][1]);
            $fats = $this->faker->numberBetween($data['fats'][0], $data['fats'][1]);
        } else {
            // Generate for pre/post workout meals
            $workoutMeals = [
                'pre_workout' => [
                    'titles' => ['Pre-Workout Energy Bar', 'Banana with Peanut Butter', 'Oatmeal with Berries'],
                    'calories' => [200, 350],
                    'protein' => [10, 20],
                    'carbs' => [30, 50],
                    'fats' => [5, 12]
                ],
                'post_workout' => [
                    'titles' => ['Protein Recovery Shake', 'Chocolate Milk', 'Greek Yogurt with Granola'],
                    'calories' => [250, 400],
                    'protein' => [20, 35],
                    'carbs' => [25, 40],
                    'fats' => [5, 15]
                ]
            ];
            
            $workoutData = $workoutMeals[$selectedType];
            $title = $this->faker->randomElement($workoutData['titles']);
            $ingredients = 'Specialized ' . strtolower($selectedType) . ' ingredients';
            $calories = $this->faker->numberBetween($workoutData['calories'][0], $workoutData['calories'][1]);
            $protein = $this->faker->numberBetween($workoutData['protein'][0], $workoutData['protein'][1]);
            $carbs = $this->faker->numberBetween($workoutData['carbs'][0], $workoutData['carbs'][1]);
            $fats = $this->faker->numberBetween($workoutData['fats'][0], $workoutData['fats'][1]);
        }

        return [
            'plan_id' => NutritionPlan::factory(),
            'title' => $title,
            'description' => $this->faker->sentence(8),
            'meal_type' => $selectedType,
            'ingredients' => $ingredients,
            'instructions' => $this->generateInstructions($selectedType),
            'image_url' => $this->faker->boolean(30) ? 'meals/' . $this->faker->uuid() . '.jpg' : null,
            'prep_time' => $this->faker->numberBetween(5, 45),
            'cook_time' => $this->faker->numberBetween(0, 60),
            'servings' => $this->faker->numberBetween(1, 4),
            'calories_per_serving' => $calories,
            'protein_per_serving' => $protein,
            'carbs_per_serving' => $carbs,
            'fats_per_serving' => $fats,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Generate realistic cooking instructions based on meal type
     *
     * @param string $mealType
     * @return string
     */
    private function generateInstructions(string $mealType): string
    {
        $instructions = [
            'breakfast' => [
                '1. Heat pan over medium heat\n2. Mix ingredients in bowl\n3. Cook for 3-5 minutes per side\n4. Serve hot with toppings',
                '1. Combine dry ingredients\n2. Add wet ingredients and mix\n3. Let sit overnight in refrigerator\n4. Top with fresh fruits before serving',
                '1. Layer ingredients in bowl\n2. Add toppings as desired\n3. Drizzle with honey or syrup\n4. Serve immediately'
            ],
            'lunch' => [
                '1. Prepare all ingredients\n2. Cook protein according to instructions\n3. Assemble salad or bowl\n4. Add dressing and serve',
                '1. Heat oil in large pan\n2. Cook vegetables until tender\n3. Add protein and seasonings\n4. Serve over grains or greens',
                '1. Prepare filling ingredients\n2. Warm tortilla or bread\n3. Assemble wrap or sandwich\n4. Cut in half and serve'
            ],
            'dinner' => [
                '1. Preheat oven to required temperature\n2. Season protein and vegetables\n3. Cook according to recipe timing\n4. Rest before serving',
                '1. Prepare sauce or marinade\n2. Cook protein until done\n3. Steam or roast vegetables\n4. Combine and serve hot',
                '1. Heat cooking oil in pan\n2. Sauté aromatics first\n3. Add remaining ingredients\n4. Simmer until cooked through'
            ],
            'snack' => [
                '1. Prepare ingredients\n2. Combine as needed\n3. Serve immediately or chill\n4. Enjoy as a healthy snack',
                '1. Mix ingredients in bowl\n2. Form into desired shape if needed\n3. Chill if required\n4. Serve portion-controlled amounts'
            ]
        ];

        $mealInstructions = $instructions[$mealType] ?? $instructions['snack'];
        return $this->faker->randomElement($mealInstructions);
    }

    /**
     * Create a breakfast meal
     *
     * @return static
     */
    public function breakfast(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'meal_type' => 'breakfast',
                'sort_order' => 1,
            ];
        });
    }

    /**
     * Create a lunch meal
     *
     * @return static
     */
    public function lunch(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'meal_type' => 'lunch',
                'sort_order' => 2,
            ];
        });
    }

    /**
     * Create a dinner meal
     *
     * @return static
     */
    public function dinner(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'meal_type' => 'dinner',
                'sort_order' => 3,
            ];
        });
    }

    /**
     * Create a high-protein meal
     *
     * @return static
     */
    public function highProtein(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'protein_per_serving' => $this->faker->numberBetween(25, 50),
                'title' => 'High-Protein ' . $attributes['title'],
            ];
        });
    }

    /**
     * Create a meal for a specific plan
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
}