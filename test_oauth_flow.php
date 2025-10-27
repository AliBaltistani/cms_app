<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Http\Controllers\GoogleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

echo "=== Google OAuth Flow Test ===\n";
echo "Testing Google OAuth integration for trainers\n\n";

// Test 1: Check environment variables
echo "1. Checking Google OAuth Environment Variables:\n";
echo "   GOOGLE_CLIENT_ID: " . (env('GOOGLE_CLIENT_ID') ? 'Set (' . substr(env('GOOGLE_CLIENT_ID'), 0, 10) . '...)' : 'NOT SET') . "\n";
echo "   GOOGLE_CLIENT_SECRET: " . (env('GOOGLE_CLIENT_SECRET') ? 'Set (' . substr(env('GOOGLE_CLIENT_SECRET'), 0, 10) . '...)' : 'NOT SET') . "\n";
echo "   GOOGLE_REDIRECT_URI: " . (env('GOOGLE_REDIRECT_URI') ?: 'NOT SET') . "\n\n";

// Test 2: Check if we have trainers
echo "2. Checking Trainers in System:\n";
$trainers = User::where('role', 'trainer')->get(['id', 'name', 'email', 'google_token']);

if ($trainers->isEmpty()) {
    echo "   ❌ No trainers found in the system.\n";
    echo "   Creating a test trainer...\n";
    
    $testTrainer = User::create([
        'name' => 'Test Trainer',
        'email' => 'test.trainer@example.com',
        'password' => bcrypt('password'),
        'role' => 'trainer',
        'phone' => '1234567890'
    ]);
    
    echo "   ✅ Test trainer created with ID: {$testTrainer->id}\n";
    $trainers = collect([$testTrainer]);
} else {
    echo "   ✅ Found {$trainers->count()} trainer(s):\n";
    foreach ($trainers->take(3) as $trainer) {
        $connected = $trainer->google_token ? '✅ Connected' : '❌ Not Connected';
        echo "      - ID: {$trainer->id}, Name: {$trainer->name}, Google: {$connected}\n";
    }
}
echo "\n";

// Test 3: Test GoogleController instantiation
echo "3. Testing GoogleController:\n";
try {
    $googleController = new GoogleController();
    echo "   ✅ GoogleController instantiated successfully\n";
    
    // Check if Google Client is properly configured
    $reflection = new ReflectionClass($googleController);
    $property = $reflection->getProperty('googleClient');
    $property->setAccessible(true);
    $googleClient = $property->getValue($googleController);
    
    if ($googleClient) {
        echo "   ✅ Google Client initialized\n";
        echo "   Client ID: " . ($googleClient->getClientId() ? 'Set' : 'NOT SET') . "\n";
        echo "   Client Secret: " . ($googleClient->getClientSecret() ? 'Set' : 'NOT SET') . "\n";
        echo "   Redirect URI: " . ($googleClient->getRedirectUri() ?: 'NOT SET') . "\n";
    } else {
        echo "   ❌ Google Client not initialized\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error instantiating GoogleController: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Test OAuth URL generation
echo "4. Testing OAuth URL Generation:\n";
try {
    $trainer = $trainers->first();
    
    // Simulate authentication
    Auth::login($trainer);
    
    // Create a mock request
    $request = Request::create('/trainer/google-calendar/connect', 'GET');
    
    // Test trainerConnect method
    $response = $googleController->trainerConnect($request);
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        $targetUrl = $response->getTargetUrl();
        if (strpos($targetUrl, 'accounts.google.com') !== false) {
            echo "   ✅ OAuth URL generated successfully\n";
            echo "   URL starts with: " . substr($targetUrl, 0, 50) . "...\n";
            
            // Check if state parameter is present
            if (strpos($targetUrl, 'state=') !== false) {
                echo "   ✅ State parameter included in OAuth URL\n";
            } else {
                echo "   ⚠️  State parameter missing from OAuth URL\n";
            }
        } else {
            echo "   ❌ Invalid OAuth URL generated: " . substr($targetUrl, 0, 100) . "\n";
        }
    } else {
        echo "   ❌ trainerConnect did not return a redirect response\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error testing OAuth URL generation: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Test callback method (without actual Google response)
echo "5. Testing OAuth Callback Method:\n";
try {
    // Test with missing code
    $callbackRequest = Request::create('/google/callback', 'GET');
    $response = $googleController->handleGoogleCallback($callbackRequest);
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "   ✅ Callback handles missing code correctly\n";
    } else {
        echo "   ❌ Callback did not handle missing code properly\n";
    }
    
    // Test with error parameter
    $errorRequest = Request::create('/google/callback', 'GET', ['error' => 'access_denied']);
    $response = $googleController->handleGoogleCallback($errorRequest);
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "   ✅ Callback handles OAuth errors correctly\n";
    } else {
        echo "   ❌ Callback did not handle OAuth errors properly\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error testing callback method: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Check route registration
echo "6. Checking Route Registration:\n";
try {
    $routes = app('router')->getRoutes();
    $googleRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'google') !== false || strpos($uri, 'oauth') !== false) {
            $googleRoutes[] = [
                'method' => implode('|', $route->methods()),
                'uri' => $uri,
                'name' => $route->getName(),
                'action' => $route->getActionName()
            ];
        }
    }
    
    if (!empty($googleRoutes)) {
        echo "   ✅ Found " . count($googleRoutes) . " Google-related routes:\n";
        foreach ($googleRoutes as $route) {
            echo "      - {$route['method']} /{$route['uri']} -> {$route['action']}\n";
        }
    } else {
        echo "   ❌ No Google-related routes found\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error checking routes: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Database connectivity
echo "7. Testing Database Connectivity:\n";
try {
    $userCount = User::count();
    echo "   ✅ Database connected successfully\n";
    echo "   Total users in system: {$userCount}\n";
    
    // Test google_token field
    $usersWithTokens = User::whereNotNull('google_token')->count();
    echo "   Users with Google tokens: {$usersWithTokens}\n";
    
} catch (Exception $e) {
    echo "   ❌ Database connection error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Test Summary ===\n";
echo "✅ = Working correctly\n";
echo "⚠️  = Warning/Needs attention\n";
echo "❌ = Error/Not working\n\n";

echo "If all tests show ✅, the OAuth flow should work correctly.\n";
echo "If you see ❌ or ⚠️, please address those issues first.\n\n";

echo "To test the complete flow:\n";
echo "1. Login as a trainer\n";
echo "2. Go to /trainer/google-calendar\n";
echo "3. Click 'Connect to Google Calendar'\n";
echo "4. Complete Google OAuth flow\n";
echo "5. Check if you're redirected back successfully\n";