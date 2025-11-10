<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Oauth2;
use Exception;

/**
 * Google Auth Controller
 * 
 * Handles Google OAuth flow for web login/register.
 * Saves user with the same fields as manual registration
 * (name, email, phone, password, role) by collecting missing phone.
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Auth
 * @category    Authentication
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class GoogleAuthController extends Controller
{
    /**
     * Google Client instance
     * 
     * @var Google_Client
     */
    private Google_Client $googleClient;

    /**
     * Constructor - Initialize Google Client with profile scopes
     * 
     * @throws Exception If required env is missing
     */
    public function __construct()
    {
        $this->googleClient = new Google_Client();

        $clientId = config('services.google.client_id', env('GOOGLE_CLIENT_ID'));
        $clientSecret = config('services.google.client_secret', env('GOOGLE_CLIENT_SECRET'));
        // Dedicated redirect for auth login/register to avoid calendar route conflicts
        $redirectUri = config('services.google_auth.redirect_uri', env('GOOGLE_AUTH_REDIRECT_URI'));

        if (empty($clientId) || empty($clientSecret) || empty($redirectUri)) {
            throw new Exception('Google OAuth is not configured. Please set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET and GOOGLE_AUTH_REDIRECT_URI');
        }

        $this->googleClient->setClientId($clientId);
        $this->googleClient->setClientSecret($clientSecret);
        $this->googleClient->setRedirectUri($redirectUri);
        $this->googleClient->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
        $this->googleClient->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
        // Unified: include Calendar scope so Google sign-in tokens support calendar operations
        $this->googleClient->addScope(Google_Service_Calendar::CALENDAR);
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('consent');
    }

    /**
     * Redirect to Google OAuth for web login/register
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function redirectToGoogle(Request $request)
    {
        try {
            // Create CSRF-protected state
            $statePayload = [
                'type' => 'auth',
                'timestamp' => time(),
                'session_id' => session()->getId(),
            ];
            $state = base64_encode(json_encode($statePayload));
            session(['google_auth_state' => $state]);
            $this->googleClient->setState($state);

            $authUrl = $this->googleClient->createAuthUrl();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'auth_url' => $authUrl,
                ]);
            }

            return redirect($authUrl);

        } catch (Exception $e) {
            Log::error('Google auth redirect error: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initiate Google auth',
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to initiate Google authentication');
        }
    }

    /**
     * Handle Google OAuth callback for web login/register
     * 
     * - If user exists by email: update google_token and login
     * - If new user: store Google profile in session, redirect to phone capture
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            $incomingState = $request->get('state');
            $expectedState = session('google_auth_state');

            if (!$code) {
                throw new Exception('Authorization code not provided');
            }

            if (!$incomingState || $incomingState !== $expectedState) {
                throw new Exception('Invalid OAuth state');
            }

            // Exchange code for token
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error'])) {
                throw new Exception('Google token error: ' . ($token['error_description'] ?? $token['error']));
            }

            // Fetch user info
            $oauth2 = new Google_Service_Oauth2($this->googleClient);
            $googleUser = $oauth2->userinfo->get();

            $email = strtolower(trim($googleUser->email ?? ''));
            $name = trim($googleUser->name ?? '');
            $avatar = $googleUser->picture ?? null;
            $googleId = $googleUser->id ?? null;

            if (!$email) {
                throw new Exception('Google account email is required');
            }

            // Try to find existing user by email
            $user = User::where('email', $email)->first();

            if ($user) {
                // Update google_token JSON for audit/linkage
                $user->google_token = [
                    'access_token' => $token['access_token'] ?? null,
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'expires_in' => $token['expires_in'] ?? null,
                    'id' => $googleId,
                    'email' => $email,
                    'avatar' => $avatar,
                ];
                if (is_null($user->email_verified_at)) {
                    $user->email_verified_at = now();
                }
                $user->save();

                Auth::login($user);

                Log::info('User logged in via Google web OAuth', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return redirect('/dashboard')->with('success', 'Logged in with Google successfully');
            }

            // New user: store details in session and request phone
            session(['google_new_user' => [
                'name' => $name ?: $email,
                'email' => $email,
                'avatar' => $avatar,
                'google' => [
                    'access_token' => $token['access_token'] ?? null,
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'expires_in' => $token['expires_in'] ?? null,
                    'id' => $googleId,
                ],
            ]]);

            return redirect()->route('auth.google.complete.form')->with('status', 'Please add your phone number to complete registration');

        } catch (Exception $e) {
            Log::error('Google auth callback failed: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google authentication failed',
                    'error' => $e->getMessage(),
                ], 400);
            }
            return redirect()->route('login')->with('error', 'Google authentication failed. Please try again.');
        }
    }

    /**
     * Show profile completion form to capture phone
     * 
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showCompleteProfileForm()
    {
        $pending = session('google_new_user');
        if (!$pending) {
            return redirect()->route('login')->with('error', 'No pending Google registration');
        }
        return view('auth.google-complete', ['pending' => $pending]);
    }

    /**
     * Complete registration by collecting phone and creating user
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function completeRegistration(Request $request)
    {
        $pending = session('google_new_user');
        if (!$pending) {
            return redirect()->route('login')->with('error', 'No pending Google registration');
        }

        // Validate phone following manual registration rules
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'in:trainer,client'],
            'phone' => ['required', 'numeric', 'min:10', 'unique:users,phone'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('auth.google.complete.form')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user = User::create([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'phone' => $request->phone,
                // Generate a secure random password so the record matches manual registration
                'password' => Hash::make(str()->random(32)),
                'role' => $request->role,
                'email_verified_at' => now(),
                'google_token' => [
                    'access_token' => $pending['google']['access_token'] ?? null,
                    'refresh_token' => $pending['google']['refresh_token'] ?? null,
                    'expires_in' => $pending['google']['expires_in'] ?? null,
                    'id' => $pending['google']['id'] ?? null,
                    'avatar' => $pending['avatar'] ?? null,
                ],
            ]);

            // Clear pending session data
            session()->forget(['google_new_user', 'google_auth_state']);

            Auth::login($user);

            Log::info('User registered via Google web OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return redirect('/dashboard')->with('success', 'Account created with Google successfully');

        } catch (Exception $e) {
            Log::error('Google registration failed: ' . $e->getMessage());
            return redirect()->route('auth.google.complete.form')
                ->with('error', 'Registration failed. Please try again.')
                ->withInput();
        }
    }
}