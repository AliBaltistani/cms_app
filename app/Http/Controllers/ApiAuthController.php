<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordReset;
use App\Services\TwilioSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Mail\PasswordResetOTP;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Google_Client;
use Google_Service_Oauth2;
use Google_Service_Calendar;

/**
 * API Authentication Controller
 * 
 * Handles all authentication operations via API including
 * registration, login, logout, password reset, and token management
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\API
 * @category    Authentication API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ApiAuthController extends ApiBaseController
{
  

    /**
     * Generate Google OAuth URL for mobile/API clients with Calendar scope.
     *
     * Provides a state token to protect the flow and a URL the client can open
     * in a browser to grant `email`, `profile`, and Calendar access. The callback
     * will be handled by `googleOAuthCallback` and returns JSON.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function googleOAuthUrl(Request $request): JsonResponse
    {
        try {
            $deviceName = $request->input('device_name', 'API Client');
            $flow = $request->input('flow', 'login'); // 'login' or 'register'

            // Prepare Google Client
            $clientId = config('services.google_auth_api.client_id', env('GOOGLE_CLIENT_ID'));
            $clientSecret = config('services.google_auth_api.client_secret', env('GOOGLE_CLIENT_SECRET'));
            $redirectUri = config('services.google_auth_api.redirect_uri', env('GOOGLE_AUTH_API_REDIRECT_URI'));

            if (empty($clientId) || empty($clientSecret) || empty($redirectUri)) {
                return $this->sendError('Configuration Error', [
                    'error' => 'Google API OAuth is not configured. Set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET and GOOGLE_AUTH_API_REDIRECT_URI.'
                ], 500);
            }

            $googleClient = new Google_Client();
            $googleClient->setClientId($clientId);
            $googleClient->setClientSecret($clientSecret);
            $googleClient->setRedirectUri($redirectUri);
            $googleClient->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
            $googleClient->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
            $googleClient->addScope(Google_Service_Calendar::CALENDAR);
            $googleClient->setAccessType('offline');
            $googleClient->setPrompt('consent');

            // Stateless flow: create and persist state in cache
            $state = base64_encode(json_encode([
                'type' => 'api_auth',
                'flow' => $flow,
                'device' => $deviceName,
                'timestamp' => time(),
            ]));

            Cache::put('google_api_state:' . $state, [
                'flow' => $flow,
                'device_name' => $deviceName,
            ], now()->addMinutes(10));

            $googleClient->setState($state);
            $authUrl = $googleClient->createAuthUrl();

            Log::info('API Google OAuth URL generated', [
                'state' => $state,
                'flow' => $flow,
                'device' => $deviceName,
                'auth_url_length' => strlen($authUrl)
            ]);

            return $this->sendResponse([
                'auth_url' => $authUrl,
                'state' => $state,
            ], 'Google OAuth URL generated');
        } catch (\Exception $e) {
            Log::error('Failed to generate API Google OAuth URL', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('OAuth URL Error', ['error' => 'Unable to generate Google OAuth URL'], 500);
        }
    }

    /**
     * Handle Google OAuth callback for mobile/API clients with Calendar scope.
     *
     * - If user exists by email: update google_token and return Sanctum token
     * - If new user: return pending status and store Google details for completion
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function googleOAuthCallback(Request $request): JsonResponse
    {
        try {
            $code = $request->query('code');
            $state = $request->query('state');

            if (!$code) {
                return $this->sendError('Authorization Error', ['error' => 'Authorization code not provided'], 400);
            }

            if (!$state) {
                return $this->sendError('State Error', ['error' => 'State parameter missing'], 400);
            }

            $cached = Cache::get('google_api_state:' . $state);
            if (!$cached) {
                return $this->sendError('State Error', ['error' => 'Invalid or expired OAuth state'], 400);
            }

            $clientId = config('services.google_auth_api.client_id', env('GOOGLE_CLIENT_ID'));
            $clientSecret = config('services.google_auth_api.client_secret', env('GOOGLE_CLIENT_SECRET'));
            $redirectUri = config('services.google_auth_api.redirect_uri', env('GOOGLE_AUTH_API_REDIRECT_URI'));

            $googleClient = new Google_Client();
            $googleClient->setClientId($clientId);
            $googleClient->setClientSecret($clientSecret);
            $googleClient->setRedirectUri($redirectUri);
            $googleClient->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
            $googleClient->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
            $googleClient->addScope(Google_Service_Calendar::CALENDAR);

            // Exchange code for token
            $token = $googleClient->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error'])) {
                return $this->sendError('Token Error', [
                    'error' => $token['error_description'] ?? $token['error']
                ], 400);
            }

            // Fetch Google user info
            $oauth2 = new Google_Service_Oauth2($googleClient);
            $googleUser = $oauth2->userinfo->get();

            $email = strtolower(trim($googleUser->email ?? ''));
            $name = trim($googleUser->name ?? '');
            $avatar = $googleUser->picture ?? null;
            $googleId = $googleUser->id ?? null;

            if (!$email) {
                return $this->sendError('Profile Error', ['error' => 'Google account email is required'], 400);
            }

            $user = User::where('email', $email)->first();
            if ($user) {
                // Update google_token with calendar-capable fields
                // Preserve existing refresh_token if Google doesn't return a new one
                $existing = is_array($user->google_token) ? $user->google_token : [];
                $user->google_token = [
                    'access_token' => $token['access_token'] ?? ($existing['access_token'] ?? null),
                    'refresh_token' => $token['refresh_token'] ?? ($existing['refresh_token'] ?? null),
                    'expires_in' => $token['expires_in'] ?? ($existing['expires_in'] ?? null),
                    'id' => $googleId ?: ($existing['id'] ?? null),
                    'email' => $email ?: ($existing['email'] ?? null),
                    'avatar' => $avatar ?: ($existing['avatar'] ?? null),
                ];
                if (is_null($user->email_verified_at)) {
                    $user->email_verified_at = now();
                }
                $user->save();

                // Create Sanctum token
                $deviceName = $cached['device_name'] ?? 'API Client';
                $apiToken = $user->createToken($deviceName)->plainTextToken;

                Cache::forget('google_api_state:' . $state);

                Log::info('User logged in via API Google OAuth', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return $this->sendResponse([
                    'token' => $apiToken,
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                        'isVerified' => !is_null($user->email_verified_at)
                    ]
                ], 'Logged in with Google successfully');
            }

            // New user: store details temporarily and require completion
            Cache::put('google_api_pending:' . $state, [
                'name' => $name ?: $email,
                'email' => $email,
                'avatar' => $avatar,
                'google' => [
                    'access_token' => $token['access_token'] ?? null,
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'expires_in' => $token['expires_in'] ?? null,
                    'id' => $googleId,
                ],
            ], now()->addMinutes(15));

            return $this->sendResponse([
                'status' => 'pending',
                'state' => $state,
                'google_user' => [
                    'email' => $email,
                    'name' => $name ?: $email,
                    'avatar' => $avatar,
                ]
            ], 'Please provide phone and role to complete registration');

        } catch (\Exception $e) {
            Log::error('API Google OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('OAuth Callback Failed', ['error' => 'Google authentication failed'], 400);
        }
    }

    /**
     * Complete registration for new users after API Google OAuth.
     *
     * Requires `state`, `phone`, and `role` (trainer|client). Creates user,
     * stores calendar-capable Google tokens, and returns a Sanctum token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completeGoogleOAuthRegistration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'state' => 'required|string',
                'phone' => 'required|string|max:20|unique:users,phone',
                'role' => 'required|string|in:trainer,client',
                'device_name' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $state = $request->input('state');
            $pending = Cache::get('google_api_pending:' . $state);
            if (!$pending) {
                return $this->sendError('State Error', ['error' => 'Pending Google registration not found or expired'], 400);
            }

            // Ensure email is not already registered (race-condition check)
            $existing = User::where('email', $pending['email'])->first();
            if ($existing) {
                return $this->sendError('Conflict', ['email' => 'User already exists. Please login.'], 409);
            }

            $user = User::create([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'phone' => $request->input('phone'),
                'password' => Hash::make(Str::random(12)), // random password for Google sign-up
                'role' => $request->input('role')
            ]);

            // Store Google tokens with calendar scope
            $user->google_token = [
                'access_token' => $pending['google']['access_token'] ?? null,
                'refresh_token' => $pending['google']['refresh_token'] ?? null,
                'expires_in' => $pending['google']['expires_in'] ?? null,
                'id' => $pending['google']['id'] ?? null,
                'avatar' => $pending['avatar'] ?? null,
                'email' => $pending['email'] ?? null,
            ];
            $user->email_verified_at = now();
            $user->save();

            // Clean up
            Cache::forget('google_api_pending:' . $state);
            Cache::forget('google_api_state:' . $state);

            $deviceName = $request->input('device_name', 'API Client');
            $apiToken = $user->createToken($deviceName)->plainTextToken;

            Log::info('User registered via API Google OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            return $this->sendResponse([
                'token' => $apiToken,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                    'isVerified' => !is_null($user->email_verified_at)
                ]
            ], 'Account created with Google successfully');

        } catch (\Exception $e) {
            Log::error('API Google registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Registration Failed', ['error' => 'Unable to complete registration'], 500);
        }
    }
    /**
     * User login via API
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate login credentials
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'device_name' => 'nullable|string|max:255'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $credentials = $request->only('email', 'password');
            
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $deviceName = $request->device_name ?? 'API Client';
                
                // Create token
                $token = $user->createToken($deviceName)->plainTextToken;
                
                // Prepare response data
                $responseData = [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                        'isVerified' => is_null($user->email_verified_at) ? false : true,
                    ]
                ];
                
                Log::info('User logged in successfully via API', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                
                return $this->sendResponse($responseData, 'Login successful');
            }
            
            return $this->sendError('Unauthorized', ['error' => 'Invalid credentials'], 401);
            
        } catch (\Exception $e) {
            Log::error('API login failed: ' . $e->getMessage(), [
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Login Failed', ['error' => 'Unable to process login request'], 500);
        }
    }
    
    /**
     * User registration via API
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            // Validate registration data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string',
                'phone' => 'required|string|max:20|unique:users,phone',
                'role' => 'required|in:trainer,client,admin',
                'device_name' => 'nullable|string|max:255'
            ]);
            
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                $errorMessage = 'Validation Error: ' . implode(' ', $errors);
                return $this->sendError($errorMessage, $validator->errors(), 422);
            }
            
            // Create new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'client'
            ]);
            
            $deviceName = $request->device_name ?? 'API Client';
            $token = $user->createToken($deviceName)->plainTextToken;
            
            // Prepare response data
            $responseData = [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile_image' => null,
                    'isVerified' => !is_null($user->email_verified_at)
                ]
            ];
            
            Log::info('User registered successfully via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);
            
            return $this->sendResponse($responseData, 'User registered successfully', 201);
            
        } catch (\Exception $e) {
            Log::error('API registration failed: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Registration Failed', ['error' => 'Unable to process registration'.$e->getTraceAsString()], 500);
        }
    }
    
    /**
     * User logout via API
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Revoke current token
            $request->user()->currentAccessToken()->delete();
            
            Log::info('User logged out successfully via API', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return $this->sendResponse([], 'Logged out successfully');
            
        } catch (\Exception $e) {
            Log::error('API logout failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Logout Failed', ['error' => 'Unable to logout'], 500);
        }
    }
    
    /**
     * Get authenticated user information
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'email_verified_at' => $user->email_verified_at,
                'isVerified' => is_null($user->email_verified_at) ? false : true,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString()
            ];
            
            return $this->sendResponse($userData, 'User information retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user info: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve user information'], 500);
        }
    }
    
    /**
     * Refresh authentication token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $deviceName = $request->device_name ?? 'API Client';
            
            // Revoke current token
            $request->user()->currentAccessToken()->delete();
            
            // Create new token
            $newToken = $user->createToken($deviceName)->plainTextToken;
            
            $responseData = [
                'token' => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => null // Sanctum tokens don't expire by default
            ];
            
            Log::info('Token refreshed successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return $this->sendResponse($responseData, 'Token refreshed successfully');
            
        } catch (\Exception $e) {
            Log::error('Token refresh failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Refresh Failed', ['error' => 'Unable to refresh token'], 500);
        }
    }
    
    /**
     * Verify token validity
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyToken(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $token = $request->user()->currentAccessToken();
            
            $tokenData = [
                'valid' => true,
                'token_name' => $token->name,
                'created_at' => $token->created_at->toISOString(),
                'last_used_at' => $token->last_used_at ? $token->last_used_at->toISOString() : null,
                'user_id' => $user->id
            ];
            
            return $this->sendResponse($tokenData, 'Token is valid');
            
        } catch (\Exception $e) {
            Log::error('Token verification failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Verification Failed', ['error' => 'Unable to verify token'], 500);
        }
    }
    
    /**
     * Send password reset OTP
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            // Determine the type of reset (email or phone)
            $type = $request->input('type', 'email'); // Default to email for backward compatibility
            
            // Dynamic validation based on type
            if ($type === 'phone') {
                $validator = Validator::make($request->all(), [
                    'phone' => 'required|string|exists:users,phone',
                    'type' => 'required|in:phone'
                ], [
                    'phone.required' => 'Phone number is required.',
                    'phone.exists' => 'No account found with this phone number.',
                    'type.in' => 'Invalid reset type specified.'
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Get user details by phone
                $user = User::where('phone', $request->phone)->first();
                $identifier = $request->phone;
                
            } else {
                // Default to email validation
                $validator = Validator::make($request->all(), [
                    'email' => 'required|email|exists:users,email',
                    'type' => 'nullable|in:email'
                ], [
                    'email.required' => 'Email address is required.',
                    'email.email' => 'Please enter a valid email address.',
                    'email.exists' => 'No account found with this email address.',
                    'type.in' => 'Invalid reset type specified.'
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Get user details by email
                $user = User::where('email', $request->email)->first();
                $identifier = $request->email;
            }
            
            if (!$user) {
                return $this->sendError('User Not Found', [$type => 'User not found.'], 404);
            }
            
            // Generate unique token and create password reset record with OTP
            $token = Str::random(60);
            
            if ($type === 'phone') {
                $passwordReset = PasswordReset::createWithPhoneOTP($request->phone, $token);
                
                // Send OTP via SMS using Twilio
                $twilioService = new TwilioSmsService();
                $smsMessage = "Your password reset OTP is: {$passwordReset->otp}. This code expires in 15 minutes.";
                
                $smsResult = $twilioService->sendSms($request->phone, $smsMessage);
                
                if (!$smsResult['success']) {
                    return $this->sendError('SMS Failed', ['error' => 'Unable to send SMS: ' . $smsResult['error']], 500);
                }
                
                Log::info('Password reset OTP sent via SMS API', [
                    'phone' => $request->phone,
                    'user_id' => $user->id,
                    'message_sid' => $smsResult['message_sid'] ?? null,
                    'otp' => $passwordReset->otp // For debugging - remove in production
                ]);
                
                return $this->sendResponse([
                    'message_sid' => $smsResult['message_sid'] ?? null,
                    'type' => 'phone'
                ], 'Password reset OTP sent to your phone number');
                
            } else {
                $passwordReset = PasswordReset::createWithOTP($request->email, $token);
                
                // Send OTP email
                Mail::to($request->email)->send(new PasswordResetOTP($passwordReset->otp, $user->name));
                
                Log::info('Password reset OTP sent via API', [
                    'email' => $request->email,
                    'user_id' => $user->id,
                    'otp' => $passwordReset->otp // For debugging - remove in production
                ]);
                
                return $this->sendResponse([
                    'type' => 'email'
                ], 'Password reset OTP sent to your email address');
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP via API: ' . $e->getMessage(), [
                'identifier' => $identifier ?? 'unknown',
                'type' => $type ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Send Failed', ['error' => 'Unable to send password reset OTP'], 500);
        }
    }
    
    /**
     * Verify password reset OTP
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOTP(Request $request): JsonResponse
    {
        try {
            // Determine the type of verification (email or phone)
            $type = $request->input('type', 'email'); // Default to email for backward compatibility
            
            // Dynamic validation based on type
            if ($type === 'phone') {
                $validator = Validator::make($request->all(), [
                    'phone' => 'required|string',
                    'otp' => 'required|string|size:6',
                    'type' => 'required|in:phone'
                ], [
                    'phone.required' => 'Phone number is required.',
                    'otp.required' => 'OTP is required.',
                    'otp.size' => 'OTP must be exactly 6 digits.',
                    'type.in' => 'Invalid verification type specified.'
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Verify OTP using model method for phone
                $passwordReset = PasswordReset::verifyPhoneOTP($request->phone, $request->otp);
                $identifier = $request->phone;
                
            } else {
                // Default to email validation
                $validator = Validator::make($request->all(), [
                    'email' => 'required|email',
                    'otp' => 'required|string|size:6',
                    'type' => 'nullable|in:email'
                ], [
                    'email.required' => 'Email address is required.',
                    'email.email' => 'Please enter a valid email address.',
                    'otp.required' => 'OTP is required.',
                    'otp.size' => 'OTP must be exactly 6 digits.',
                    'type.in' => 'Invalid verification type specified.'
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Verify OTP using model method for email
                $passwordReset = PasswordReset::verifyOTP($request->email, $request->otp);
                $identifier = $request->email;
            }
            
            if (!$passwordReset) {
                return $this->sendError('Invalid OTP', ['otp' => 'Invalid or expired OTP. Please try again.'], 400);
            }
            
            // Generate reset token for password reset
            $resetToken = Str::random(64);
            
            Log::info('Password reset OTP verified via API', [
                $type => $identifier,
                'type' => $type
            ]);
            
            $responseData = [
                'reset_token' => $resetToken,
                'type' => $type,
                'expires_in' => 900 // 15 minutes
            ];
            
            // Add the identifier to response
            $responseData[$type] = $identifier;
            
            return $this->sendResponse($responseData, 'OTP verified successfully');
            
        } catch (\Exception $e) {
            Log::error('OTP verification failed via API: ' . $e->getMessage(), [
                'identifier' => $identifier ?? 'unknown',
                'type' => $type ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Verification Failed', ['error' => 'Unable to verify OTP'], 500);
        }
    }
    
    /**
     * Reset password using verified token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            // Determine the type of reset (email or phone)
            $type = $request->input('type', 'email'); // Default to email for backward compatibility
            
            // Dynamic validation based on type
            if ($type === 'phone') {
                $validator = Validator::make($request->all(), [
                    'phone' => 'required|string',
                    'otp' => 'required|string|size:6',
                    'password' => 'required|string|min:8|confirmed',
                    'password_confirmation' => 'required|string',
                    'type' => 'required|in:phone'
                ], [
                    'phone.required' => 'Phone number is required.',
                    'otp.required' => 'OTP is required.',
                    'otp.size' => 'OTP must be exactly 6 digits.',
                    'password.required' => 'Password is required.',
                    'password.min' => 'Password must be at least 8 characters long.',
                    'password.confirmed' => 'Password confirmation does not match.',
                    'password_confirmation.required' => 'Password confirmation is required.',
                    'type.in' => 'Invalid reset type specified.'
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Verify OTP again for security
                $passwordReset = PasswordReset::verifyPhoneOTP($request->phone, $request->otp);
                
                if (!$passwordReset) {
                    return $this->sendError('Invalid OTP', ['otp' => 'Invalid or expired OTP. Please try again.'], 400);
                }
                
                // Find user and update password
                $user = User::where('phone', $request->phone)->first();
                $identifier = $request->phone;
                
            } else {
                // Default to email validation
                $validator = Validator::make($request->all(), [
                    'email' => 'required|email',
                    'otp' => 'required|string|size:6',
                    'password' => 'required|string|min:8|confirmed',
                    'password_confirmation' => 'required|string',
                    'type' => 'nullable|in:email'
                ], [
                    'email.required' => 'Email address is required.',
                    'email.email' => 'Please enter a valid email address.',
                    'otp.required' => 'OTP is required.',
                    'otp.size' => 'OTP must be exactly 6 digits.',
                    'password.required' => 'Password is required.',
                    'password.min' => 'Password must be at least 8 characters long.',
                    'password.confirmed' => 'Password confirmation does not match.',
                    'password_confirmation.required' => 'Password confirmation is required.',
                    'type.in' => 'Invalid reset type specified.'
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Verify OTP again for security
                $passwordReset = PasswordReset::verifyOTP($request->email, $request->otp);
                
                if (!$passwordReset) {
                    return $this->sendError('Invalid OTP', ['otp' => 'Invalid or expired OTP. Please try again.'], 400);
                }
                
                // Find user and update password
                $user = User::where('email', $request->email)->first();
                $identifier = $request->email;
            }
            
            if (!$user) {
                return $this->sendError('User Not Found', [$type => 'User not found.'], 404);
            }
            
            // Update user password
            $user->password = Hash::make($request->password);
            $user->save();
            
            // Mark OTP as used and clean up
            $passwordReset->markOTPAsUsed();
            
            // Revoke all existing tokens for security
            $user->tokens()->delete();
            
            Log::info('Password reset successfully via API', [
                $type => $identifier,
                'user_id' => $user->id,
                'type' => $type
            ]);
            
            return $this->sendResponse([
                'type' => $type
            ], 'Password reset successfully! Please login with your new password.');
            
        } catch (\Exception $e) {
            Log::error('Password reset failed via API: ' . $e->getMessage(), [
                'identifier' => $identifier ?? 'unknown',
                'type' => $type ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Reset Failed', ['error' => 'Unable to reset password'], 500);
        }
    }
    
    /**
     * Resend password reset OTP
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendOTP(Request $request): JsonResponse
    {
        try {
            // Determine the type of reset (email or phone)
            $type = $request->input('type', 'email'); // Default to email for backward compatibility
            
            // Dynamic validation based on type
            if ($type === 'phone') {
                $validator = Validator::make($request->all(), [
                    'phone' => 'required|string|exists:users,phone',
                    'type' => 'required|in:phone'
                ], [
                    'phone.required' => 'Phone number is required.',
                    'phone.exists' => 'No account found with this phone number.',
                    'type.in' => 'Invalid reset type specified.'
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Check if there's a recent OTP request (rate limiting)
                $recentReset = PasswordReset::where('phone', $request->phone)
                    ->where('otp_type', PasswordReset::OTP_TYPE_PHONE)
                    ->where('created_at', '>', now()->subMinutes(2))
                    ->first();
                
                if ($recentReset) {
                    return $this->sendError('Rate Limited', ['error' => 'Please wait 2 minutes before requesting another OTP'], 429);
                }
                
                // Get user details
                $user = User::where('phone', $request->phone)->first();
                $identifier = $request->phone;
                
                if (!$user) {
                    return $this->sendError('User Not Found', ['phone' => 'User not found.'], 404);
                }
                
                // Generate new token and create new password reset record with OTP
                $token = Str::random(60);
                $passwordReset = PasswordReset::createWithPhoneOTP($request->phone, $token);
                
                // Send new OTP via SMS
                $twilioService = new TwilioSmsService();
                $smsMessage = "Your password reset OTP is: {$passwordReset->otp}. This code expires in 15 minutes.";
                
                $smsResult = $twilioService->sendSms($request->phone, $smsMessage);
                
                if (!$smsResult['success']) {
                    return $this->sendError('SMS Failed', ['error' => 'Unable to send SMS: ' . $smsResult['error']], 500);
                }
                
                Log::info('Password reset OTP resent via SMS API', [
                    'phone' => $request->phone,
                    'user_id' => $user->id,
                    'message_sid' => $smsResult['message_sid'] ?? null,
                    'otp' => $passwordReset->otp // For debugging - remove in production
                ]);
                
                return $this->sendResponse([
                    'message_sid' => $smsResult['message_sid'] ?? null,
                    'type' => $type
                ], 'New OTP has been sent to your phone number');
                
            } else {
                // Default to email validation
                $validator = Validator::make($request->all(), [
                    'email' => 'required|email|exists:users,email',
                    'type' => 'nullable|in:email'
                ], [
                    'email.required' => 'Email address is required.',
                    'email.email' => 'Please enter a valid email address.',
                    'email.exists' => 'No account found with this email address.',
                    'type.in' => 'Invalid reset type specified.'
                ]);
                
                if ($validator->fails()) {
                    return $this->sendError('Validation Error', $validator->errors(), 422);
                }
                
                // Check if there's a recent OTP request (rate limiting)
                $recentReset = PasswordReset::where('email', $request->email)
                    ->where('created_at', '>', now()->subMinutes(2))
                    ->first();
                
                if ($recentReset) {
                    return $this->sendError('Rate Limited', ['error' => 'Please wait 2 minutes before requesting another OTP'], 429);
                }
                
                // Get user details
                $user = User::where('email', $request->email)->first();
                $identifier = $request->email;
                
                if (!$user) {
                    return $this->sendError('User Not Found', ['email' => 'User not found.'], 404);
                }
                
                // Generate new token and create new password reset record with OTP
                $token = Str::random(60);
                $passwordReset = PasswordReset::createWithOTP($request->email, $token);
                
                // Send new OTP email
                Mail::to($request->email)->send(new PasswordResetOTP($passwordReset->otp, $user->name));
                
                Log::info('Password reset OTP resent via API', [
                    'email' => $request->email,
                    'user_id' => $user->id,
                    'otp' => $passwordReset->otp // For debugging - remove in production
                ]);
                
                return $this->sendResponse([
                    'type' => $type
                ], 'New OTP has been sent to your email address');
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP via API: ' . $e->getMessage(), [
                'identifier' => $identifier ?? 'unknown',
                'type' => $type ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Resend Failed', ['error' => 'Unable to resend OTP'], 500);
        }
    }








}
