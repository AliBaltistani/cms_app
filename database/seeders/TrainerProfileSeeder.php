<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\Testimonial;
use App\Models\TestimonialLikesDislike;
use Illuminate\Support\Facades\Hash;

/**
 * TrainerProfileSeeder
 * 
 * Seeds the database with trainer profile data including:
 * - 2 trainers with complete profiles
 * - 5 certifications distributed among trainers
 * - 10 testimonials for trainers
 * - 5 likes/dislikes reactions
 */
class TrainerProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 2 trainers with detailed profiles
        $trainer1 = User::updateOrCreate(
            ['email' => 'sarah.trainer@example.com'],
            [
            'name' => 'Sarah Johnson',
            'email' => 'sarah.trainer@example.com',
            'phone' => '+92-300-1234567',
            'role' => 'trainer',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'designation' => 'Senior Fitness Trainer & Nutritionist',
            'experience' => '5_years',
            'about' => 'Passionate fitness trainer with over 5 years of experience helping clients achieve their health and wellness goals. Specialized in strength training, weight loss, and nutrition coaching. I believe in creating personalized workout plans that fit your lifestyle and help you build sustainable healthy habits.',
            'training_philosophy' => 'My philosophy centers around the belief that fitness should be enjoyable, sustainable, and tailored to each individual. I focus on building both physical strength and mental resilience, ensuring my clients not only reach their goals but maintain them long-term through proper education and motivation.'
            ]
        );
        
        $trainer2 = User::updateOrCreate(
            ['email' => 'ahmed.trainer@example.com'],
            [
            'name' => 'Ahmed Khan',
            'email' => 'ahmed.trainer@example.com', 
            'phone' => '+92-301-9876543',
            'role' => 'trainer',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'designation' => 'CrossFit Coach & Athletic Performance Specialist',
            'experience' => '7_years',
            'about' => 'Certified CrossFit coach and athletic performance specialist with 7 years of experience training athletes and fitness enthusiasts. Expert in functional movements, Olympic lifting, and high-intensity training. Committed to helping clients push their limits safely while achieving peak performance.',
            'training_philosophy' => 'I believe in the power of functional fitness and community-driven training. My approach combines scientific training principles with motivational coaching to help clients discover their true potential. Every workout is an opportunity to become stronger, both physically and mentally.'
            ]
        );
        
        // Create some clients for testimonials
        $clients = User::factory()->count(6)->create([
            'role' => 'client'
        ]);
        
        // Create 5 certifications distributed among trainers
        UserCertification::create([
            'user_id' => $trainer1->id,
            'certificate_name' => 'Certified Personal Trainer (NASM-CPT)',
            'doc' => 'certifications/sarah_nasm_cpt.pdf'
        ]);
        
        UserCertification::create([
            'user_id' => $trainer1->id,
            'certificate_name' => 'Precision Nutrition Level 1',
            'doc' => 'certifications/sarah_nutrition.pdf'
        ]);
        
        UserCertification::create([
            'user_id' => $trainer1->id,
            'certificate_name' => 'Yoga Teacher Training (200-hour)',
            'doc' => null
        ]);
        
        UserCertification::create([
            'user_id' => $trainer2->id,
            'certificate_name' => 'CrossFit Level 2 Trainer',
            'doc' => 'certifications/ahmed_crossfit_l2.pdf'
        ]);
        
        UserCertification::create([
            'user_id' => $trainer2->id,
            'certificate_name' => 'Olympic Weightlifting Coach',
            'doc' => 'certifications/ahmed_olympic_lifting.jpg'
        ]);
        
        // Create 10 testimonials (5 for each trainer)
        $testimonials = [];
        
        // Testimonials for Sarah (trainer1)
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer1->id,
            'client_id' => $clients[0]->id,
            'name' => $clients[0]->name,
            'date' => now()->subDays(30)->format('Y-m-d'),
            'rate' => 5,
            'comments' => 'Sarah is an amazing trainer! She helped me lose 15kg in 4 months and taught me so much about nutrition. Her personalized approach made all the difference.',
            'likes' => 12,
            'dislikes' => 0
        ]);
        
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer1->id,
            'client_id' => $clients[1]->id,
            'name' => $clients[1]->name,
            'date' => now()->subDays(45)->format('Y-m-d'),
            'rate' => 5,
            'comments' => 'Professional, knowledgeable, and very motivating. Sarah\'s yoga sessions helped me improve my flexibility and reduce stress significantly.',
            'likes' => 8,
            'dislikes' => 1
        ]);
        
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer1->id,
            'client_id' => $clients[2]->id,
            'name' => $clients[2]->name,
            'date' => now()->subDays(60)->format('Y-m-d'),
            'rate' => 4,
            'comments' => 'Great trainer with excellent knowledge of nutrition. The meal plans were very helpful and easy to follow.',
            'likes' => 6,
            'dislikes' => 0
        ]);
        
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer1->id,
            'client_id' => $clients[3]->id,
            'name' => $clients[3]->name,
            'date' => now()->subDays(15)->format('Y-m-d'),
            'rate' => 5,
            'comments' => 'Sarah\'s training style is perfect for beginners. She made me feel comfortable and confident in the gym.',
            'likes' => 10,
            'dislikes' => 0
        ]);
        
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer1->id,
            'client_id' => $clients[4]->id,
            'name' => $clients[4]->name,
            'date' => now()->subDays(90)->format('Y-m-d'),
            'rate' => 5,
            'comments' => 'Excellent results in just 3 months! Sarah\'s holistic approach to fitness and nutrition is outstanding.',
            'likes' => 15,
            'dislikes' => 1
        ]);
        
        // Testimonials for Ahmed (trainer2)
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer2->id,
            'client_id' => $clients[0]->id,
            'name' => $clients[0]->name,
            'date' => now()->subDays(20)->format('Y-m-d'),
            'rate' => 5,
            'comments' => 'Ahmed\'s CrossFit classes are intense but incredibly effective. I\'ve never been stronger or more confident.',
            'likes' => 18,
            'dislikes' => 0
        ]);
        
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer2->id,
            'client_id' => $clients[1]->id,
            'name' => $clients[1]->name,
            'date' => now()->subDays(35)->format('Y-m-d'),
            'rate' => 4,
            'comments' => 'Great coach for Olympic lifting. Ahmed\'s technique corrections helped me lift safely and effectively.',
            'likes' => 9,
            'dislikes' => 2
        ]);
        
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer2->id,
            'client_id' => $clients[2]->id,
            'name' => $clients[2]->name,
            'date' => now()->subDays(50)->format('Y-m-d'),
            'rate' => 5,
            'comments' => 'Ahmed pushes you to your limits while keeping safety as the top priority. Excellent athletic performance coach.',
            'likes' => 14,
            'dislikes' => 0
        ]);
        
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer2->id,
            'client_id' => $clients[3]->id,
            'name' => $clients[3]->name,
            'date' => now()->subDays(25)->format('Y-m-d'),
            'rate' => 5,
            'comments' => 'The functional fitness approach really works! Ahmed helped me improve my overall athleticism significantly.',
            'likes' => 11,
            'dislikes' => 1
        ]);
        
        $testimonials[] = Testimonial::create([
            'trainer_id' => $trainer2->id,
            'client_id' => $clients[4]->id,
            'name' => $clients[4]->name,
            'date' => now()->subDays(40)->format('Y-m-d'),
            'rate' => 4,
            'comments' => 'High-intensity training with Ahmed is challenging but rewarding. Great community atmosphere in his classes.',
            'likes' => 7,
            'dislikes' => 0
        ]);
        
        // Create 5 likes/dislikes reactions from various users
        TestimonialLikesDislike::create([
            'testimonial_id' => $testimonials[0]->id,
            'user_id' => $clients[5]->id,
            'like' => true,
            'dislike' => false
        ]);
        
        TestimonialLikesDislike::create([
            'testimonial_id' => $testimonials[1]->id,
            'user_id' => $clients[5]->id,
            'like' => true,
            'dislike' => false
        ]);
        
        TestimonialLikesDislike::create([
            'testimonial_id' => $testimonials[5]->id,
            'user_id' => $clients[2]->id,
            'like' => true,
            'dislike' => false
        ]);
        
        TestimonialLikesDislike::create([
            'testimonial_id' => $testimonials[6]->id,
            'user_id' => $clients[3]->id,
            'like' => false,
            'dislike' => true
        ]);
        
        TestimonialLikesDislike::create([
            'testimonial_id' => $testimonials[8]->id,
            'user_id' => $clients[4]->id,
            'like' => true,
            'dislike' => false
        ]);
        
        $this->command->info('Trainer Profile Seeder completed successfully!');
        $this->command->info('Created:');
        $this->command->info('- 2 trainers with complete profiles');
        $this->command->info('- 6 clients for testimonials');
        $this->command->info('- 5 certifications');
        $this->command->info('- 10 testimonials');
        $this->command->info('- 5 likes/dislikes reactions');
    }
}
