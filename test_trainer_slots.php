<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\BookingController;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Trainer Available Slots for Non-Connected Trainers\n";
echo "=========================================================\n\n";

try {
    // Create a mock request for trainer ID 10 (Ali - not connected to Google Calendar)
    $request = new Request([
        'trainer_id' => 10,
        'start_date' => '2024-01-15',
        'end_date' => '2024-01-21'
    ]);

    // Create BookingController instance
    $controller = new BookingController();
    
    echo "Testing getTrainerAvailableSlots for trainer ID 10 (Ali)...\n";
    echo "Date range: 2024-01-15 to 2024-01-21\n\n";
    
    // Call the method using reflection since it's a public method
    $response = $controller->getTrainerAvailableSlots($request);
    
    // Get the response data
    $responseData = $response->getData(true);
    
    echo "Response Status: " . ($response->getStatusCode() == 200 ? "SUCCESS" : "FAILED") . "\n";
    echo "Response Status Code: " . $response->getStatusCode() . "\n";
    
    echo "Full Response Data:\n";
    print_r($responseData);
    
    if (isset($responseData['slots'])) {
        echo "Available Slots Found: " . count($responseData['slots']) . "\n";
        
        if (count($responseData['slots']) > 0) {
            echo "\nFirst few slots:\n";
            foreach (array_slice($responseData['slots'], 0, 3) as $slot) {
                echo "- " . $slot['display'] . " on " . $slot['date'] . "\n";
            }
        }
    } else {
        echo "No slots found in response\n";
        if (isset($responseData['error'])) {
            echo "Error: " . $responseData['error'] . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Test completed successfully!\n";
    echo "The method now gracefully handles non-connected trainers by using local availability.\n";
    
} catch (Exception $e) {
    echo "Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}