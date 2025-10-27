<?php

require_once 'bootstrap/app.php';
$app = $app ?? require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking Trainer Data\n";
echo "====================\n\n";

// Check if trainer exists
$trainer = App\Models\User::find(10);
if ($trainer) {
    echo "Trainer found: {$trainer->name} (Role: {$trainer->role})\n";
    
    // Check availability
    $availability = App\Models\Availability::where('trainer_id', 10)->get();
    echo "Availability records: {$availability->count()}\n\n";
    
    if ($availability->count() > 0) {
        echo "Weekly Availability:\n";
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        foreach ($availability as $avail) {
            $dayName = $days[$avail->day_of_week] ?? "Day {$avail->day_of_week}";
            echo "- {$dayName}: ";
            
            if ($avail->morning_available) {
                echo "Morning ({$avail->morning_start_time} - {$avail->morning_end_time}) ";
            }
            if ($avail->evening_available) {
                echo "Evening ({$avail->evening_start_time} - {$avail->evening_end_time}) ";
            }
            if (!$avail->morning_available && !$avail->evening_available) {
                echo "Not Available";
            }
            echo "\n";
        }
    } else {
        echo "No availability set up for this trainer.\n";
    }
} else {
    echo "Trainer ID 10 not found\n\n";
    
    // Find trainers that exist
    $trainers = App\Models\User::where('role', 'trainer')->take(5)->get();
    echo "Available trainers:\n";
    foreach ($trainers as $t) {
        echo "- ID: {$t->id}, Name: {$t->name}\n";
    }
}

echo "\n====================\n";
echo "Check completed!\n";