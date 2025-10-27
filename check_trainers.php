<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Checking trainers in the system:\n";
echo "================================\n";

$trainers = User::where('role', 'trainer')->get(['id', 'name', 'email', 'google_token']);

if ($trainers->isEmpty()) {
    echo "No trainers found in the system.\n";
} else {
    foreach ($trainers as $trainer) {
        echo "ID: {$trainer->id}\n";
        echo "Name: {$trainer->name}\n";
        echo "Email: {$trainer->email}\n";
        echo "Google Connected: " . ($trainer->google_token ? 'Yes' : 'No') . "\n";
        echo "---\n";
    }
}

echo "\nTotal users by role:\n";
$usersByRole = User::select('role')->get()->groupBy('role');
foreach ($usersByRole as $role => $users) {
    echo "{$role}: {$users->count()} users\n";
}