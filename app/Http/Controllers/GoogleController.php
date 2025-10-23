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
            $code = $request->get('code');
            
            if (!$code) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authorization code not provided'
                    ], 400);
                }
                return redirect()->route('admin.dashboard')->with('error', 'Google authorization failed');
            }

            // Exchange code for access token
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                Log::error('Google OAuth token error: ' . json_encode($token));
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to obtain access token'
                    ], 400);
                }
                return redirect()->route('admin.dashboard')->with('error', 'Google authorization failed');
            }

            // Get user info to verify the connection
            $this->googleClient->setAccessToken($token);
            $oauth2 = new \Google_Service_Oauth2($this->googleClient);
            $userInfo = $oauth2->userinfo->get();

            // Store token for authenticated user
            $user = Auth::user();
            $user->google_token = $token;
            $user->save();

            Log::info('Google Calendar connected for trainer: ' . $user->id);

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

            return redirect()->route('admin.dashboard')->with('success', 'Google Calendar connected successfully');

        } catch (Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect Google Calendar'
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
                        $oauth2 = new \Google_Service_Oauth2($this->googleClient);
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
            $oauth2 = new \Google_Service_Oauth2($client);
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
}