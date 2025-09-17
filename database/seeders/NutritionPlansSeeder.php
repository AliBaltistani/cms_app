<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\NutritionPlan;
use App\Models\NutritionMeal;
use App\Models\NutritionMacro;
use App\Models\NutritionRestriction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * NutritionPlansSeeder
 * 
 * Comprehensive seeder for the nutrition plans module that creates realistic
 * test data with proper relationships between plans, meals, macros, and restrictions
 * 
 * @package Database\Seeders
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionPlansSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Log::info('Starting Nutrition Plans seeding...');
        
        DB::beginTransaction();
        
        try {
            // Ensure we have users to work with
            $this->ensureUsersExist();
            
            // Create global nutrition plans (admin-created)
            $this->createGlobalPlans();
            
            // Create trainer-specific plans
            $this->createTrainerPlans();
            
            // Create assigned client plans
            $this->createClientPlans();
            
            // Create sample meal plans with full data
            $this->createCompleteMealPlans();
            
            DB::commit();
            
            Log::info('Nutrition Plans seeding completed successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Nutrition Plans seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ensure we have users with appropriate roles
     *
     * @return void
     */
    private function ensureUsersExist(): void
    {
        // Check if we have trainers and clients
        $trainersCount = User::where('role', 'trainer')->count();
        $clientsCount = User::where('role', 'client')->count();
        
        if ($trainersCount < 3) {
            Log::info('Creating additional trainer users for nutrition plans seeding...');
            User::factory()->count(3 - $trainersCount)->create([
                'role' => 'trainer',
                'email_verified_at' => now(),
            ]);
        }
        
        if ($clientsCount < 5) {
            Log::info('Creating additional client users for nutrition plans seeding...');
            User::factory()->count(5 - $clientsCount)->create([
                'role' => 'client',
                'email_verified_at' => now(),
            ]);
        }
    }

    /**
     * Create global nutrition plans (admin-created templates)
     *
     * @return void
     */
    private function createGlobalPlans(): void
    {
        Log::info('Creating global nutrition plans...');
        
        $globalPlans = [
            [
                'plan_name' => 'Beginner Weight Loss Plan',
                'description' => 'A comprehensive weight loss plan designed for beginners with balanced nutrition and sustainable habits.',
                'goal_type' => 'weight_loss',
                'duration_days' => 90,
                'target_weight' => 70.0,
                'tags' => ['beginner', 'weight-loss', 'balanced'],
                'status' => 'active'
            ],
            [
                'plan_name' => 'Advanced Muscle Building Protocol',
                'description' => 'High-protein nutrition plan optimized for serious muscle building and strength gains.',
                'goal_type' => 'muscle_gain',
                'duration_days' => 180,
                'target_weight' => 85.0,
                'tags' => ['advanced', 'muscle-gain', 'high-protein'],
                'status' => 'active'
            ],
            [
                'plan_name' => 'Keto Transformation Guide',
                'description' => 'Complete ketogenic diet plan for rapid fat loss and metabolic transformation.',
                'goal_type' => 'weight_loss',
                'duration_days' => 120,
                'target_weight' => 65.0,
                'tags' => ['keto', 'low-carb', 'fat-loss'],
                'status' => 'active'
            ],
            [
                'plan_name' => 'Mediterranean Wellness Plan',
                'description' => 'Heart-healthy Mediterranean diet approach for overall wellness and longevity.',
                'goal_type' => 'maintenance',
                'duration_days' => 365,
                'target_weight' => 75.0,
                'tags' => ['mediterranean', 'heart-healthy', 'wellness'],
                'status' => 'active'
            ],
            [
                'plan_name' => 'Vegetarian Fitness Plan',
                'description' => 'Plant-based nutrition plan optimized for fitness enthusiasts and athletes.',
                'goal_type' => 'maintenance',
                'duration_days' => 180,
                'target_weight' => 70.0,
                'tags' => ['vegetarian', 'plant-based', 'fitness'],
                'status' => 'active'
            ]
        ];
        
        foreach ($globalPlans as $planData) {
            $plan = NutritionPlan::factory()->global()->create($planData);
            
            // Add meals to each global plan
            $this->addMealsToGlobalPlan($plan);
            
            // Add daily macro targets
            $this->addDailyMacros($plan);
            
            // Add appropriate restrictions
            $this->addRestrictionsToGlobalPlan($plan);
        }
        
        Log::info('Created ' . count($globalPlans) . ' global nutrition plans.');
    }

    /**
     * Create trainer-specific nutrition plans
     *
     * @return void
     */
    private function createTrainerPlans(): void
    {
        Log::info('Creating trainer-specific nutrition plans...');
        
        $trainers = User::where('role', 'trainer')->get();
        
        foreach ($trainers as $trainer) {
            // Each trainer gets 2-4 plans
            $planCount = rand(2, 4);
            
            for ($i = 0; $i < $planCount; $i++) {
                $plan = NutritionPlan::factory()->create([
                    'trainer_id' => $trainer->id,
                    'client_id' => null, // Unassigned trainer plans
                    'is_global' => false,
                    'status' => 'active'
                ]);
                
                // Add meals (3-6 meals per plan)
                $mealCount = rand(3, 6);
                for ($j = 0; $j < $mealCount; $j++) {
                    NutritionMeal::factory()->forPlan($plan->id)->create();
                }
                
                // Add daily macros
                NutritionMacro::factory()->dailyTarget()->forPlan($plan->id)->create();
                
                // 70% chance of having restrictions
                if (rand(1, 100) <= 70) {
                    NutritionRestriction::factory()->forPlan($plan->id)->create();
                }
            }
        }
        
        Log::info('Created trainer-specific nutrition plans for ' . $trainers->count() . ' trainers.');
    }

    /**
     * Create nutrition plans assigned to specific clients
     *
     * @return void
     */
    private function createClientPlans(): void
    {
        Log::info('Creating client-assigned nutrition plans...');
        
        $trainers = User::where('role', 'trainer')->get();
        $clients = User::where('role', 'client')->get();
        
        // Assign 1-2 plans to each client
        foreach ($clients as $client) {
            $trainer = $trainers->random();
            $planCount = rand(1, 2);
            
            for ($i = 0; $i < $planCount; $i++) {
                $plan = NutritionPlan::factory()->forTrainerAndClient($trainer->id, $client->id)->create();
                
                // Add comprehensive meal plan
                $this->addCompleteMealPlan($plan);
                
                // Add daily macros
                NutritionMacro::factory()->dailyTarget()->forPlan($plan->id)->create();
                
                // Add meal-specific macros for some meals
                $meals = $plan->meals()->take(3)->get();
                foreach ($meals as $meal) {
                    NutritionMacro::factory()->forMeal($meal->id)->create([
                        'plan_id' => $plan->id
                    ]);
                }
                
                // Add restrictions (80% chance for assigned plans)
                if (rand(1, 100) <= 80) {
                    NutritionRestriction::factory()->forPlan($plan->id)->create();
                }
            }
        }
        
        Log::info('Created client-assigned nutrition plans for ' . $clients->count() . ' clients.');
    }

    /**
     * Create complete meal plans with detailed nutrition data
     *
     * @return void
     */
    private function createCompleteMealPlans(): void
    {
        Log::info('Creating complete meal plans with detailed data...');
        
        // Create 3 showcase plans with complete data
        $showcasePlans = [
            [
                'plan_name' => 'Complete Daily Meal Plan - Weight Loss',
                'description' => 'Comprehensive daily meal plan with breakfast, lunch, dinner, and snacks for sustainable weight loss.',
                'goal_type' => 'weight_loss',
                'duration_days' => 60,
                'target_weight' => 68.0,
                'tags' => ['complete', 'weight-loss', 'daily-plan'],
                'restriction_type' => 'none'
            ],
            [
                'plan_name' => 'Vegetarian Muscle Building Plan',
                'description' => 'Plant-based muscle building plan with high-protein vegetarian meals and supplements.',
                'goal_type' => 'muscle_gain',
                'duration_days' => 120,
                'target_weight' => 78.0,
                'tags' => ['vegetarian', 'muscle-gain', 'plant-based'],
                'restriction_type' => 'vegetarian'
            ],
            [
                'plan_name' => 'Keto Fat Loss Protocol',
                'description' => 'Strict ketogenic meal plan for rapid fat loss with detailed macro tracking.',
                'goal_type' => 'weight_loss',
                'duration_days' => 90,
                'target_weight' => 62.0,
                'tags' => ['keto', 'fat-loss', 'low-carb'],
                'restriction_type' => 'keto'
            ]
        ];
        
        $trainer = User::where('role', 'trainer')->first();
        $client = User::where('role', 'client')->first();
        
        foreach ($showcasePlans as $showcaseData) {
            $restrictionType = $showcaseData['restriction_type'];
            unset($showcaseData['restriction_type']);
            
            $plan = NutritionPlan::factory()->create(array_merge($showcaseData, [
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
                'is_global' => false,
                'status' => 'active'
            ]));
            
            // Add complete daily meal structure
            $this->addCompleteDailyMeals($plan);
            
            // Add appropriate macros
            if ($restrictionType === 'keto') {
                NutritionMacro::factory()->keto()->forPlan($plan->id)->create();
            } else {
                NutritionMacro::factory()->dailyTarget()->forPlan($plan->id)->create();
            }
            
            // Add restrictions based on type
            switch ($restrictionType) {
                case 'vegetarian':
                    NutritionRestriction::factory()->vegetarian()->forPlan($plan->id)->create();
                    break;
                case 'keto':
                    NutritionRestriction::factory()->keto()->forPlan($plan->id)->create();
                    break;
                default:
                    NutritionRestriction::factory()->noRestrictions()->forPlan($plan->id)->create();
                    break;
            }
        }
        
        Log::info('Created ' . count($showcasePlans) . ' complete showcase meal plans.');
    }

    /**
     * Add meals to global plans
     *
     * @param NutritionPlan $plan
     * @return void
     */
    private function addMealsToGlobalPlan(NutritionPlan $plan): void
    {
        $mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];
        
        foreach ($mealTypes as $index => $mealType) {
            NutritionMeal::factory()->create([
                'plan_id' => $plan->id,
                'meal_type' => $mealType,
                'sort_order' => $index + 1
            ]);
        }
    }

    /**
     * Add complete meal plan to a nutrition plan
     *
     * @param NutritionPlan $plan
     * @return void
     */
    private function addCompleteMealPlan(NutritionPlan $plan): void
    {
        // Add 4-7 meals per plan
        $mealCount = rand(4, 7);
        $mealTypes = ['breakfast', 'lunch', 'dinner', 'snack', 'pre_workout', 'post_workout'];
        
        for ($i = 0; $i < $mealCount; $i++) {
            $mealType = $mealTypes[$i % count($mealTypes)];
            
            NutritionMeal::factory()->create([
                'plan_id' => $plan->id,
                'meal_type' => $mealType,
                'sort_order' => $i + 1
            ]);
        }
    }

    /**
     * Add complete daily meal structure
     *
     * @param NutritionPlan $plan
     * @return void
     */
    private function addCompleteDailyMeals(NutritionPlan $plan): void
    {
        $dailyStructure = [
            ['type' => 'breakfast', 'order' => 1],
            ['type' => 'snack', 'order' => 2],
            ['type' => 'lunch', 'order' => 3],
            ['type' => 'snack', 'order' => 4],
            ['type' => 'dinner', 'order' => 5],
            ['type' => 'pre_workout', 'order' => 6],
            ['type' => 'post_workout', 'order' => 7]
        ];
        
        foreach ($dailyStructure as $meal) {
            NutritionMeal::factory()->create([
                'plan_id' => $plan->id,
                'meal_type' => $meal['type'],
                'sort_order' => $meal['order']
            ]);
        }
    }

    /**
     * Add daily macros to a plan
     *
     * @param NutritionPlan $plan
     * @return void
     */
    private function addDailyMacros(NutritionPlan $plan): void
    {
        $macroType = 'balanced';
        
        // Adjust macro type based on plan goal and tags
        if (in_array('keto', $plan->tags ?? [])) {
            $macroType = 'keto';
        } elseif (in_array('high-protein', $plan->tags ?? [])) {
            $macroType = 'high_protein';
        } elseif ($plan->goal_type === 'muscle_gain') {
            $macroType = 'high_protein';
        }
        
        if ($macroType === 'keto') {
            NutritionMacro::factory()->keto()->forPlan($plan->id)->create();
        } elseif ($macroType === 'high_protein') {
            NutritionMacro::factory()->highProtein()->forPlan($plan->id)->create();
        } else {
            NutritionMacro::factory()->dailyTarget()->forPlan($plan->id)->create();
        }
    }

    /**
     * Add restrictions to global plans based on their characteristics
     *
     * @param NutritionPlan $plan
     * @return void
     */
    private function addRestrictionsToGlobalPlan(NutritionPlan $plan): void
    {
        $tags = $plan->tags ?? [];
        
        if (in_array('keto', $tags)) {
            NutritionRestriction::factory()->keto()->forPlan($plan->id)->create();
        } elseif (in_array('vegetarian', $tags)) {
            NutritionRestriction::factory()->vegetarian()->forPlan($plan->id)->create();
        } elseif (in_array('heart-healthy', $tags)) {
            NutritionRestriction::factory()->create([
                'plan_id' => $plan->id,
                'heart_healthy' => true,
                'low_sodium' => true,
                'mediterranean' => true,
                'notes' => 'Heart-healthy nutrition plan with Mediterranean approach and low sodium content.'
            ]);
        } else {
            // 60% chance of having some restrictions for other plans
            if (rand(1, 100) <= 60) {
                NutritionRestriction::factory()->forPlan($plan->id)->create();
            } else {
                NutritionRestriction::factory()->noRestrictions()->forPlan($plan->id)->create();
            }
        }
    }
}