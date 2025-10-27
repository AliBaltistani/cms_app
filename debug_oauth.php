<?php

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Google OAuth URL Debug ===\n";

// Check environment variables
echo "1. Environment Variables:\n";
echo "   GOOGLE_CLIENT_ID: " . (env('GOOGLE_CLIENT_ID') ? 'Set (' . substr(env('GOOGLE_CLIENT_ID'), 0, 10) . '...)' : 'NOT SET') . "\n";
echo "   GOOGLE_CLIENT_SECRET: " . (env('GOOGLE_CLIENT_SECRET') ? 'Set (' . substr(env('GOOGLE_CLIENT_SECRET'), 0, 10) . '...)' : 'NOT SET') . "\n";
echo "   GOOGLE_REDIRECT_URI: " . env('GOOGLE_REDIRECT_URI') . "\n\n";

// Test Google Client directly
echo "2. Testing Google Client:\n";
try {
    $googleClient = new Google_Client();
    $googleClient->setClientId(env('GOOGLE_CLIENT_ID'));
    $googleClient->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
    $googleClient->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
    $googleClient->addScope(Google_Service_Calendar::CALENDAR);
    $googleClient->setAccessType('offline');
    $googleClient->setPrompt('consent');
    
    echo "   ✅ Google Client created successfully\n";
    
    // Generate OAuth URL
    $authUrl = $googleClient->createAuthUrl();
    echo "   OAuth URL: " . $authUrl . "\n";
    
    // Check if URL is valid
    if (strpos($authUrl, 'accounts.google.com') !== false) {
        echo "   ✅ Valid Google OAuth URL generated\n";
    } else {
        echo "   ❌ Invalid OAuth URL - doesn't contain Google domain\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";