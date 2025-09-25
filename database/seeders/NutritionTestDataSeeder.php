<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\NutritionPlan;
use App\Models\NutritionMeal;
use App\Models\NutritionMacro;
use App\Models\NutritionRecommendation;
use App\Models\FoodDiary;
use Carbon\Carbon;

/**
 * NutritionTestDataSeeder
 * 
 * Creates comprehensive test data for the nutrition management system
 * Includes plans, meals, recommendations, and food diary entries
 * 
 * @package Database\Seeders
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users if they don't exist
        $trainer = User::where('email', 'trainer@test.com')->first();
        if (!$trainer) {
            // Check if phone number is already taken
            $existingPhone = User::where('phone', '+1234567890')->first();
            $trainerPhone = $existingPhone ? '+1234567892' : '+1234567890';
            
            $trainer = User::create([
                'name' => 'Test Trainer',
                'email' => 'trainer@test.com',
                'role' => 'trainer',
                'phone' => $trainerPhone,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        $client = User::where('email', 'client@test.com')->first();
        if (!$client) {
            // Check if phone number is already taken
            $existingPhone = User::where('phone', '+1234567891')->first();
            $clientPhone = $existingPhone ? '+1234567893' : '+1234567891';
            
            $client = User::create([
                'name' => 'Test Client',
                'email' => 'client@test.com',
                'role' => 'client',
                'phone' => $clientPhone,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create nutrition plans with different categories
        $plans = [
            [
                'plan_name' => 'Weight Loss Plan',
                'description' => 'A comprehensive weight loss nutrition plan designed to help you lose weight safely and effectively.',
                'category' => 'weight_loss',
                'goal_type' => 'weight_loss',
                'duration_days' => 90,
                'target_weight' => 70.00,
                'status' => 'active',
                'is_global' => true,
                'tags' => json_encode(['weight-loss', 'low-calorie', 'balanced']),
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
            ],
            [
                'plan_name' => 'Muscle Gain Plan',
                'description' => 'High-protein nutrition plan designed to support muscle growth and strength training.',
                'category' => 'muscle_gain',
                'goal_type' => 'muscle_gain',
                'duration_days' => 120,
                'target_weight' => 80.00,
                'status' => 'active',
                'is_global' => true,
                'tags' => json_encode(['muscle-gain', 'high-protein', 'strength']),
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
            ],
            [
                'plan_name' => 'Maintenance Plan',
                'description' => 'Balanced nutrition plan for maintaining current weight and overall health.',
                'category' => 'maintenance',
                'goal_type' => 'maintenance',
                'duration_days' => 365,
                'target_weight' => 75.00,
                'status' => 'active',
                'is_global' => false,
                'tags' => json_encode(['maintenance', 'balanced', 'lifestyle']),
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
            ]
        ];

        foreach ($plans as $index => $planData) {
            $plan = NutritionPlan::create($planData);

            // Create daily macros for each plan
            $macroData = [
                ['calories' => 1800, 'protein' => 120, 'carbs' => 180, 'fats' => 60], // Weight loss
                ['calories' => 2800, 'protein' => 180, 'carbs' => 300, 'fats' => 100], // Muscle gain
                ['calories' => 2200, 'protein' => 140, 'carbs' => 220, 'fats' => 80], // Maintenance
            ];

            NutritionMacro::create([
                'plan_id' => $plan->id,
                'total_calories' => $macroData[$index]['calories'],
                'protein' => $macroData[$index]['protein'],
                'carbs' => $macroData[$index]['carbs'],
                'fats' => $macroData[$index]['fats'],
                'macro_type' => 'daily_target',
                'notes' => 'Daily macro targets for ' . $plan->plan_name,
            ]);

            // Create meals for each plan
            $mealData = [
                // Weight loss meals
                [
                    ['title' => 'Protein Breakfast Bowl', 'description' => 'High-protein breakfast with eggs, oats, and berries', 'meal_type' => 'breakfast', 'prep_time' => 15, 'cook_time' => 10, 'servings' => 1, 'calories' => 450, 'protein' => 25, 'carbs' => 35, 'fats' => 18, 'ingredients' => json_encode(['2 whole eggs', '1/2 cup rolled oats', '1/4 cup mixed berries', '1 tbsp almond butter', '1 cup almond milk']), 'instructions' => json_encode(['Cook oats with almond milk', 'Scramble eggs in a non-stick pan', 'Top oats with eggs, berries, and almond butter', 'Serve immediately']), 'sort_order' => 1],
                    ['title' => 'Grilled Chicken Salad', 'description' => 'Fresh salad with grilled chicken breast and vegetables', 'meal_type' => 'lunch', 'prep_time' => 20, 'cook_time' => 15, 'servings' => 1, 'calories' => 380, 'protein' => 35, 'carbs' => 15, 'fats' => 20, 'ingredients' => json_encode(['150g chicken breast', '2 cups mixed greens', '1/2 cucumber', '1 tomato', '2 tbsp olive oil', '1 tbsp balsamic vinegar']), 'instructions' => json_encode(['Season and grill chicken breast', 'Chop vegetables', 'Mix greens with vegetables', 'Slice chicken and add to salad', 'Drizzle with olive oil and vinegar']), 'sort_order' => 2],
                    ['title' => 'Baked Salmon with Vegetables', 'description' => 'Omega-3 rich salmon with roasted vegetables', 'meal_type' => 'dinner', 'prep_time' => 10, 'cook_time' => 25, 'servings' => 1, 'calories' => 420, 'protein' => 30, 'carbs' => 20, 'fats' => 25, 'ingredients' => json_encode(['150g salmon fillet', '1 cup broccoli', '1/2 cup sweet potato', '1 tbsp olive oil', 'herbs and spices']), 'instructions' => json_encode(['Preheat oven to 400°F', 'Season salmon with herbs', 'Cut vegetables and toss with olive oil', 'Bake salmon and vegetables for 20-25 minutes']), 'sort_order' => 3],
                ],
                // Muscle gain meals
                [
                    ['title' => 'Power Smoothie Bowl', 'description' => 'High-calorie smoothie bowl with protein powder and nuts', 'meal_type' => 'breakfast', 'prep_time' => 10, 'cook_time' => 0, 'servings' => 1, 'calories' => 650, 'protein' => 35, 'carbs' => 60, 'fats' => 25, 'ingredients' => json_encode(['1 scoop protein powder', '1 banana', '1/2 cup oats', '2 tbsp peanut butter', '1 cup whole milk', '1 tbsp honey']), 'instructions' => json_encode(['Blend all ingredients until smooth', 'Pour into bowl', 'Top with granola and fresh fruits']), 'sort_order' => 1],
                    ['title' => 'Beef and Rice Bowl', 'description' => 'Lean beef with brown rice and vegetables', 'meal_type' => 'lunch', 'prep_time' => 15, 'cook_time' => 20, 'servings' => 1, 'calories' => 720, 'protein' => 45, 'carbs' => 65, 'fats' => 20, 'ingredients' => json_encode(['200g lean ground beef', '1 cup brown rice', '1/2 cup black beans', '1/4 avocado', 'mixed vegetables']), 'instructions' => json_encode(['Cook brown rice', 'Brown the ground beef', 'Steam vegetables', 'Combine all ingredients in a bowl']), 'sort_order' => 2],
                    ['title' => 'Chicken and Quinoa', 'description' => 'Grilled chicken breast with quinoa and roasted vegetables', 'meal_type' => 'dinner', 'prep_time' => 20, 'cook_time' => 30, 'servings' => 1, 'calories' => 680, 'protein' => 50, 'carbs' => 55, 'fats' => 18, 'ingredients' => json_encode(['200g chicken breast', '3/4 cup quinoa', '1 cup mixed vegetables', '2 tbsp olive oil']), 'instructions' => json_encode(['Cook quinoa according to package instructions', 'Grill seasoned chicken breast', 'Roast vegetables with olive oil', 'Serve chicken over quinoa with vegetables']), 'sort_order' => 3],
                ],
                // Maintenance meals
                [
                    ['title' => 'Balanced Breakfast', 'description' => 'Well-balanced breakfast with whole grains and protein', 'meal_type' => 'breakfast', 'prep_time' => 12, 'cook_time' => 8, 'servings' => 1, 'calories' => 520, 'protein' => 28, 'carbs' => 45, 'fats' => 22, 'ingredients' => json_encode(['2 eggs', '2 slices whole grain toast', '1/2 avocado', '1 cup Greek yogurt', '1 tbsp honey']), 'instructions' => json_encode(['Toast bread', 'Scramble eggs', 'Mash avocado on toast', 'Serve with Greek yogurt and honey']), 'sort_order' => 1],
                    ['title' => 'Mediterranean Wrap', 'description' => 'Healthy wrap with hummus, vegetables, and lean protein', 'meal_type' => 'lunch', 'prep_time' => 15, 'cook_time' => 0, 'servings' => 1, 'calories' => 480, 'protein' => 25, 'carbs' => 50, 'fats' => 18, 'ingredients' => json_encode(['1 whole wheat tortilla', '3 tbsp hummus', '100g turkey breast', '1/4 cup cucumber', '1/4 cup tomatoes', '2 tbsp feta cheese']), 'instructions' => json_encode(['Spread hummus on tortilla', 'Add turkey and vegetables', 'Sprinkle with feta cheese', 'Roll tightly and slice']), 'sort_order' => 2],
                    ['title' => 'Stir-Fry Dinner', 'description' => 'Colorful vegetable stir-fry with tofu and brown rice', 'meal_type' => 'dinner', 'prep_time' => 18, 'cook_time' => 15, 'servings' => 1, 'calories' => 550, 'protein' => 30, 'carbs' => 60, 'fats' => 20, 'ingredients' => json_encode(['150g firm tofu', '2/3 cup brown rice', '1 cup mixed stir-fry vegetables', '2 tbsp soy sauce', '1 tbsp sesame oil']), 'instructions' => json_encode(['Cook brown rice', 'Cube and pan-fry tofu', 'Stir-fry vegetables with soy sauce', 'Serve tofu and vegetables over rice']), 'sort_order' => 3],
                ],
            ];

            foreach ($mealData[$index] as $meal) {
                $meal['plan_id'] = $plan->id;
                NutritionMeal::create($meal);
            }

            // Create nutrition recommendations for each plan
            $recommendationData = [
                ['target_calories' => 1800, 'protein' => 120, 'carbs' => 180, 'fats' => 60], // Weight loss
                ['target_calories' => 2800, 'protein' => 180, 'carbs' => 300, 'fats' => 100], // Muscle gain
                ['target_calories' => 2200, 'protein' => 140, 'carbs' => 220, 'fats' => 80], // Maintenance
            ];

            NutritionRecommendation::create([
                'plan_id' => $plan->id,
                'target_calories' => $recommendationData[$index]['target_calories'],
                'protein' => $recommendationData[$index]['protein'],
                'carbs' => $recommendationData[$index]['carbs'],
                'fats' => $recommendationData[$index]['fats'],
            ]);
        }

        // Create sample food diary entries for the client
        $foodDiaryEntries = [
            [
                'client_id' => $client->id,
                'meal_name' => 'Greek Yogurt with Berries',
                'calories' => 150,
                'protein' => 15,
                'carbs' => 20,
                'fats' => 5,
                'logged_at' => Carbon::now()->subDays(2)->setTime(8, 30),
            ],
            [
                'client_id' => $client->id,
                'meal_name' => 'Grilled Chicken Breast',
                'calories' => 250,
                'protein' => 35,
                'carbs' => 0,
                'fats' => 12,
                'logged_at' => Carbon::now()->subDays(2)->setTime(12, 30),
            ],
            [
                'client_id' => $client->id,
                'meal_name' => 'Quinoa Salad',
                'calories' => 180,
                'protein' => 8,
                'carbs' => 30,
                'fats' => 6,
                'logged_at' => Carbon::now()->subDays(1)->setTime(13, 0),
            ],
            [
                'client_id' => $client->id,
                'meal_name' => 'Protein Smoothie',
                'calories' => 220,
                'protein' => 25,
                'carbs' => 15,
                'fats' => 8,
                'logged_at' => Carbon::now()->subDays(1)->setTime(16, 0),
            ],
            [
                'client_id' => $client->id,
                'meal_name' => 'Baked Salmon',
                'calories' => 300,
                'protein' => 28,
                'carbs' => 5,
                'fats' => 18,
                'logged_at' => Carbon::now()->setTime(19, 0),
            ],
            [
                'client_id' => $client->id,
                'meal_name' => 'Mixed Nuts Snack',
                'calories' => 160,
                'protein' => 6,
                'carbs' => 8,
                'fats' => 14,
                'logged_at' => Carbon::now()->setTime(15, 30),
            ],
        ];

        foreach ($foodDiaryEntries as $entry) {
            FoodDiary::create($entry);
        }

        $this->command->info('Nutrition test data created successfully!');
        $this->command->info('Created:');
        $this->command->info('- 3 nutrition plans with different categories');
        $this->command->info('- 9 nutrition meals (3 per plan)');
        $this->command->info('- 9 nutrition recommendations (3 per plan)');
        $this->command->info('- 6 food diary entries');
        $this->command->info('- Test trainer: trainer@test.com (password: password)');
        $this->command->info('- Test client: client@test.com (password: password)');
    }
}
