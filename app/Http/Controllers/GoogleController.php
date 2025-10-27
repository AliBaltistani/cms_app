<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Oauth2;
use Exception;

/**
 * Google OAuth Controller
 * 
 * Handles Google OAuth flow for trainer Google Calendar integration
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    Google Calendar Integration
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class GoogleController extends Controller
{
    /**
     * Google Client instance
     * 
     * @var Google_Client
     */
    private $googleClient;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->googleClient = new Google_Client();
        $this->googleClient->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->googleClient->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $this->googleClient->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $this->googleClient->addScope(Google_Service_Calendar::CALENDAR);
        $this->googleClient->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
        $this->googleClient->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('consent');
    }

    /**
     * Redirect to Google OAuth
     * 
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle(Request $request)
    {
        try {
            // Check if user is authenticated and is a trainer
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication required'
                    ], 401);
                }
                return redirect()->route('login');
            }

            $user = Auth::user();
            if ($user->role !== 'trainer') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only trainers can connect Google Calendar'
                    ], 403);
                }
                return redirect()->back()->with('error', 'Only trainers can connect Google Calendar');
            }

            // Generate OAuth URL
            $authUrl = $this->googleClient->createAuthUrl();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'auth_url' => $authUrl,
                    'message' => 'Google OAuth URL generated successfully'
                ]);
            }

            return redirect($authUrl);

        } catch (Exception $e) {
            Log::error('Google OAuth redirect error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate Google OAuth URL'
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to connect to Google Calendar');
        }
    }

    /**
     * Handle Google OAuth callback
     * 
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Log all callback parameters for debugging
            Log::info('Google OAuth callback received', [
                'code' => $request->get('code') ? 'present' : 'missing',
                'error' => $request->get('error'),
                'state' => $request->get('state'),
                'user_authenticated' => Auth::check(),
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'all_params' => $request->all()
            ]);

            $code = $request->get('code');
            $error = $request->get('error');
            $state = $request->get('state');
            
            if ($error) {
                Log::error('Google OAuth error received', [
                    'error' => $error,
                    'error_description' => $request->get('error_description')
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Google authorization denied: ' . $error
                    ], 400);
                }
                return redirect()->route('admin.dashboard')->with('error', 'Google authorization denied');
            }

            if (!$code) {
                Log::error('Google OAuth callback: No authorization code provided', [
                    'query_params' => $request->query(),
                    'full_url' => $request->fullUrl()
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authorization code not provided'
                    ], 400);
                }
                return redirect()->route('admin.dashboard')->with('error', 'Google authorization failed');
            }

            // Verify state parameter for security
            if ($state) {
                $sessionState = session('google_oauth_state');
                if (!$sessionState || $sessionState !== $state) {
                    Log::error('Google OAuth callback: Invalid state parameter', [
                        'received_state' => $state,
                        'session_state' => $sessionState ? 'present' : 'missing',
                        'session_id' => session()->getId()
                    ]);
                    
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid OAuth state. Please try again.'
                        ], 400);
                    }
                    return redirect()->route('login')->with('error', 'Invalid OAuth state. Please try again.');
                }

                // Decode and verify state data
                try {
                    $stateData = json_decode(base64_decode($state), true);
                    if (!$stateData || !isset($stateData['user_id'], $stateData['timestamp'])) {
                        throw new Exception('Invalid state data structure');
                    }

                    // Check if state is not too old (5 minutes max)
                    if (time() - $stateData['timestamp'] > 300) {
                        throw new Exception('OAuth state expired');
                    }

                    Log::info('OAuth state verified successfully', [
                        'user_id' => $stateData['user_id'],
                        'state_age' => time() - $stateData['timestamp']
                    ]);

                } catch (Exception $e) {
                    Log::error('Google OAuth callback: State verification failed', [
                        'error' => $e->getMessage(),
                        'state' => $state
                    ]);
                    
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid OAuth state format. Please try again.'
                        ], 400);
                    }
                    return redirect()->route('login')->with('error', 'Invalid OAuth state. Please try again.');
                }

                // Clear the state from session
                session()->forget('google_oauth_state');
            }

            // Check if user is authenticated
            if (!Auth::check()) {
                Log::error('Google OAuth callback: User not authenticated', [
                    'session_id' => session()->getId(),
                    'state' => $state
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User session expired. Please login and try again.'
                    ], 401);
                }
                return redirect()->route('login')->with('error', 'Please login first to connect Google Calendar');
            }

            $user = Auth::user();
            
            // Verify user is a trainer
            if ($user->role !== 'trainer') {
                Log::warning('Non-trainer user attempting Google OAuth', [
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only trainers can connect Google Calendar'
                    ], 403);
                }
                return redirect()->route('admin.dashboard')->with('error', 'Only trainers can connect Google Calendar');
            }

            Log::info('Attempting to exchange authorization code for token', [
                'user_id' => $user->id,
                'code_length' => strlen($code)
            ]);

            // Exchange code for access token
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                Log::error('Google OAuth token exchange failed', [
                    'error' => $token['error'],
                    'error_description' => $token['error_description'] ?? 'No description',
                    'user_id' => $user->id
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to obtain access token: ' . $token['error']
                    ], 400);
                }
                return redirect()->route('admin.dashboard')->with('error', 'Google authorization failed');
            }

            Log::info('Token exchange successful', [
                'user_id' => $user->id,
                'has_access_token' => isset($token['access_token']),
                'has_refresh_token' => isset($token['refresh_token']),
                'expires_in' => $token['expires_in'] ?? 'unknown'
            ]);

            // Validate token structure
            if (!isset($token['access_token'])) {
                Log::error('Invalid token structure - missing access_token', [
                    'user_id' => $user->id,
                    'token_keys' => array_keys($token)
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid token received from Google'
                    ], 400);
                }
                return redirect()->route('admin.dashboard')->with('error', 'Google authorization failed');
            }

            // Set access token and verify it's set correctly
            $this->googleClient->setAccessToken($token);
            
            // Verify the token is properly set
            $currentToken = $this->googleClient->getAccessToken();
            Log::info('Access token set in Google Client', [
                'user_id' => $user->id,
                'token_set' => !empty($currentToken),
                'has_access_token_in_client' => isset($currentToken['access_token'])
            ]);

            // Get user info to verify the connection
            try {
                $oauth2 = new Google_Service_Oauth2($this->googleClient);
                $userInfo = $oauth2->userinfo->get();
            } catch (Exception $e) {
                Log::error('Failed to get Google user info', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'token_in_client' => !empty($this->googleClient->getAccessToken())
                ]);
                
                // Still save the token even if user info fails
                $user->google_token = $token;
                $user->save();
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Google Calendar connected successfully (user info verification skipped)'
                    ]);
                }
                return redirect()->route('trainer.google.index')->with('success', 'Google Calendar connected successfully!');
            }

            Log::info('Google user info retrieved', [
                'user_id' => $user->id,
                'google_email' => $userInfo->email,
                'google_name' => $userInfo->name
            ]);

            // Store token for authenticated user
            $user->google_token = $token;
            $user->save();

            Log::info('Google Calendar connected successfully', [
                'user_id' => $user->id,
                'google_email' => $userInfo->email
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Google Calendar connected successfully',
                    'data' => [
                        'connected_email' => $userInfo->email,
                        'connected_at' => now()->toISOString()
                    ]
                ]);
            }

            return redirect()->route('trainer.google-calendar')->with('success', 'Google Calendar connected successfully');

        } catch (Exception $e) {
            Log::error('Google OAuth callback exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect Google Calendar: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('admin.dashboard')->with('error', 'Failed to connect Google Calendar');
        }
    }

    /**
     * Disconnect Google account
     * 
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function disconnectGoogle(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->role !== 'trainer') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only trainers can disconnect Google Calendar'
                    ], 403);
                }
                return redirect()->back()->with('error', 'Unauthorized action');
            }

            // Revoke token if exists
            if ($user->google_token) {
                try {
                    $this->googleClient->setAccessToken($user->google_token);
                    $this->googleClient->revokeToken();
                } catch (Exception $e) {
                    Log::warning('Failed to revoke Google token: ' . $e->getMessage());
                }
            }

            // Remove token from database
            $user->google_token = null;
            $user->save();

            Log::info('Google Calendar disconnected for trainer: ' . $user->id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Google Calendar disconnected successfully'
                ]);
            }

            return redirect()->back()->with('success', 'Google Calendar disconnected successfully');

        } catch (Exception $e) {
            Log::error('Google disconnect error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to disconnect Google Calendar'
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to disconnect Google Calendar');
        }
    }

    /**
     * Get Google connection status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getConnectionStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only trainers can check Google Calendar connection'
                ], 403);
            }

            $isConnected = !empty($user->google_token);
            $connectedEmail = null;

            if ($isConnected) {
                try {
                    $this->googleClient->setAccessToken($user->google_token);
                    
                    // Check if token is still valid
                    if ($this->googleClient->isAccessTokenExpired()) {
                        // Try to refresh token
                        if ($this->googleClient->getRefreshToken()) {
                            $newToken = $this->googleClient->fetchAccessTokenWithRefreshToken();
                            if (!isset($newToken['error'])) {
                                $user->google_token = $newToken;
                                $user->save();
                            } else {
                                $isConnected = false;
                            }
                        } else {
                            $isConnected = false;
                        }
                    }

                    if ($isConnected) {
                        $oauth2 = new Google_Service_Oauth2($this->googleClient);
                        $userInfo = $oauth2->userinfo->get();
                        $connectedEmail = $userInfo->email;
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to verify Google token: ' . $e->getMessage());
                    $isConnected = false;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_connected' => $isConnected,
                    'connected_email' => $connectedEmail,
                    'last_checked' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Google connection status error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check Google Calendar connection status'
            ], 500);
        }
    }

    /**
     * Get connection status for a specific trainer
     * 
     * @param User $trainer
     * @return array
     */
    public function getTrainerConnectionStatus(User $trainer): array
    {
        if (!$trainer->google_token || $trainer->role !== 'trainer') {
            return [
                'connected' => false,
                'email' => null
            ];
        }

        try {
            // Initialize Google Client
            $client = new Google_Client();
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
            $client->addScope(Google_Service_Calendar::CALENDAR);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            // Set access token
            $client->setAccessToken($trainer->google_token);

            // Check if token is expired and refresh if needed
            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $newToken = $client->fetchAccessTokenWithRefreshToken();
                    if (isset($newToken['error'])) {
                        return [
                            'connected' => false,
                            'email' => null
                        ];
                    }
                    $trainer->google_token = $newToken;
                    $trainer->save();
                } else {
                    return [
                        'connected' => false,
                        'email' => null
                    ];
                }
            }

            // Get user info to retrieve email
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            $email = $userInfo->email;

            return [
                'connected' => true,
                'email' => $email
            ];

        } catch (Exception $e) {
            Log::error('Failed to check trainer Google connection status', [
                'trainer_id' => $trainer->id,
                'error' => $e->getMessage()
            ]);

            return [
                'connected' => false,
                'email' => null
            ];
        }
    }

    /**
     * Trainer-specific Google OAuth redirect
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function trainerConnect(Request $request)
    {
        try {
            // Check if user is authenticated and is a trainer
            if (!Auth::check()) {
                Log::warning('Unauthenticated user attempting Google OAuth');
                return redirect()->route('login')->with('error', 'Please login first');
            }

            $user = Auth::user();
            if ($user->role !== 'trainer') {
                Log::warning('Non-trainer user attempting Google OAuth', [
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
                return redirect()->route('trainer.dashboard')->with('error', 'Only trainers can connect Google Calendar');
            }

            // Generate state parameter for security and session tracking
            $state = base64_encode(json_encode([
                'user_id' => $user->id,
                'timestamp' => time(),
                'session_id' => session()->getId(),
                'csrf_token' => csrf_token()
            ]));

            // Store state in session for verification
            session(['google_oauth_state' => $state]);

            // Set state parameter in Google Client
            $this->googleClient->setState($state);

            // Generate OAuth URL with state parameter
            $authUrl = $this->googleClient->createAuthUrl();

            Log::info('Trainer Google OAuth redirect initiated', [
                'user_id' => $user->id,
                'session_id' => session()->getId(),
                'auth_url_length' => strlen($authUrl)
            ]);

            return redirect($authUrl);

        } catch (Exception $e) {
            Log::error('Trainer Google OAuth redirect error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return redirect()->route('trainer.google.index')->with('error', 'Failed to connect to Google Calendar');
        }
    }

    /**
     * Trainer-specific Google OAuth callback
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function trainerCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            
            if (!$code) {
                return redirect()->route('trainer.google.index')->with('error', 'Google authorization failed');
            }

            // Exchange code for access token
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                Log::error('Trainer Google OAuth token error: ' . json_encode($token));
                return redirect()->route('trainer.google.index')->with('error', 'Google authorization failed');
            }

            // Get user info to verify the connection
            $this->googleClient->setAccessToken($token);
            $oauth2 = new Google_Service_Oauth2($this->googleClient);
            $userInfo = $oauth2->userinfo->get();

            // Store token for authenticated trainer
            $user = Auth::user();
            $user->google_token = $token;
            $user->save();

            Log::info('Google Calendar connected for trainer: ' . $user->id);

            return redirect()->route('trainer.google.index')->with('success', 'Google Calendar connected successfully! Connected as: ' . $userInfo->email);

        } catch (Exception $e) {
            Log::error('Trainer Google OAuth callback error: ' . $e->getMessage());
            return redirect()->route('trainer.google.index')->with('error', 'Failed to connect Google Calendar');
        }
    }

    /**
     * Trainer-specific Google connection status check
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function trainerStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only trainers can check Google Calendar connection'
                ], 403);
            }

            $isConnected = !empty($user->google_token);
            $connectedEmail = null;

            if ($isConnected) {
                try {
                    $this->googleClient->setAccessToken($user->google_token);
                    
                    // Check if token is still valid
                    if ($this->googleClient->isAccessTokenExpired()) {
                        // Try to refresh token
                        if ($this->googleClient->getRefreshToken()) {
                            $newToken = $this->googleClient->fetchAccessTokenWithRefreshToken();
                            if (!isset($newToken['error'])) {
                                $user->google_token = $newToken;
                                $user->save();
                            } else {
                                $isConnected = false;
                            }
                        } else {
                            $isConnected = false;
                        }
                    }

                    if ($isConnected) {
                        $oauth2 = new Google_Service_Oauth2($this->googleClient);
                        $userInfo = $oauth2->userinfo->get();
                        $connectedEmail = $userInfo->email;
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to verify trainer Google token: ' . $e->getMessage());
                    $isConnected = false;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_connected' => $isConnected,
                    'connected_email' => $connectedEmail,
                    'last_checked' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Trainer Google connection status error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check Google Calendar connection status'
            ], 500);
        }
    }

    /**
     * Trainer-specific Google disconnect
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function trainerDisconnect(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only trainers can disconnect Google Calendar'
                ], 403);
            }

            // Revoke token if exists
            if ($user->google_token) {
                try {
                    $this->googleClient->setAccessToken($user->google_token);
                    $this->googleClient->revokeToken();
                } catch (Exception $e) {
                    Log::warning('Failed to revoke trainer Google token: ' . $e->getMessage());
                }
            }

            // Remove token from database
            $user->google_token = null;
            $user->save();

            Log::info('Google Calendar disconnected for trainer: ' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar disconnected successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Trainer Google disconnect error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect Google Calendar'
            ], 500);
        }
    }

    /**
     * Admin-initiated trainer Google OAuth redirect
     * 
     * @param Request $request
     * @param int $trainerId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function adminInitiatedTrainerConnect(Request $request, $trainerId)
    {
        try {
            // Check if user is authenticated and is an admin
            if (!Auth::check()) {
                Log::warning('Unauthenticated user attempting admin-initiated Google OAuth');
                return redirect()->route('login')->with('error', 'Please login first');
            }

            $user = Auth::user();
            if ($user->role !== 'admin') {
                Log::warning('Non-admin user attempting admin-initiated Google OAuth', [
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
                return redirect()->route('admin.dashboard')->with('error', 'Only admins can initiate trainer Google Calendar connections');
            }

            // Verify the trainer exists and is a trainer
            $trainer = User::findOrFail($trainerId);
            if ($trainer->role !== 'trainer') {
                return redirect()->route('admin.bookings.google-calendar')->with('error', 'Selected user is not a trainer');
            }

            // Generate state parameter for security and session tracking
            $state = base64_encode(json_encode([
                'admin_id' => $user->id,
                'trainer_id' => $trainer->id,
                'timestamp' => time(),
                'session_id' => session()->getId(),
                'csrf_token' => csrf_token(),
                'type' => 'admin_initiated'
            ]));

            // Store state in session for verification
            session(['google_oauth_state' => $state]);

            // Set state parameter in Google Client
            $this->googleClient->setState($state);

            // Generate OAuth URL with state parameter
            $authUrl = $this->googleClient->createAuthUrl();

            Log::info('Admin-initiated trainer Google OAuth redirect initiated', [
                'admin_id' => $user->id,
                'trainer_id' => $trainer->id,
                'session_id' => session()->getId(),
                'auth_url_length' => strlen($authUrl)
            ]);

            return redirect($authUrl);

        } catch (Exception $e) {
            Log::error('Admin-initiated trainer Google OAuth redirect error', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return redirect()->route('admin.bookings.google-calendar')->with('error', 'Failed to connect trainer to Google Calendar');
        }
    }

    /**
     * Admin-initiated trainer Google OAuth callback
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function adminInitiatedTrainerCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            
            if (!$code) {
                return redirect()->route('admin.bookings.google-calendar')->with('error', 'Google authorization failed');
            }

            // Verify state parameter
            $sessionState = session('google_oauth_state');
            if (!$sessionState || $sessionState !== $state) {
                Log::warning('Admin-initiated Google OAuth state mismatch', [
                    'session_state' => $sessionState,
                    'request_state' => $state
                ]);
                return redirect()->route('admin.bookings.google-calendar')->with('error', 'Invalid OAuth state. Please try again.');
            }

            // Decode state to get admin and trainer info
            $stateData = json_decode(base64_decode($state), true);
            if (!$stateData || !isset($stateData['admin_id']) || !isset($stateData['trainer_id']) || $stateData['type'] !== 'admin_initiated') {
                return redirect()->route('admin.bookings.google-calendar')->with('error', 'Invalid OAuth state data');
            }

            // Verify admin is still authenticated
            if (!Auth::check() || Auth::id() != $stateData['admin_id']) {
                return redirect()->route('login')->with('error', 'Admin session expired. Please login and try again.');
            }

            // Get the trainer
            $trainer = User::findOrFail($stateData['trainer_id']);
            if ($trainer->role !== 'trainer') {
                return redirect()->route('admin.bookings.google-calendar')->with('error', 'Selected user is not a trainer');
            }

            // Exchange code for access token
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                Log::error('Admin-initiated trainer Google OAuth token error: ' . json_encode($token));
                return redirect()->route('admin.bookings.google-calendar')->with('error', 'Google authorization failed');
            }

            // Get user info to verify the connection
            $this->googleClient->setAccessToken($token);
            $oauth2 = new Google_Service_Oauth2($this->googleClient);
            $userInfo = $oauth2->userinfo->get();

            // Store token for the trainer
            $trainer->google_token = $token;
            $trainer->save();

            // Clear the state from session
            session()->forget('google_oauth_state');

            Log::info('Google Calendar connected for trainer by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'connected_email' => $userInfo->email
            ]);

            return redirect()->route('admin.bookings.google-calendar')->with('success', 'Google Calendar connected successfully for trainer ' . $trainer->name . '! Connected as: ' . $userInfo->email);

        } catch (Exception $e) {
            Log::error('Admin-initiated trainer Google OAuth callback error: ' . $e->getMessage());
            return redirect()->route('admin.bookings.google-calendar')->with('error', 'Failed to connect trainer Google Calendar');
        }
    }
}