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
                'role' => 'required|in:trainer,client',
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
            // Validate email input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ], [
                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'email.exists' => 'No account found with this email address.'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Get user details
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return $this->sendError('User Not Found', ['email' => 'User not found.'], 404);
            }
            
            // Generate unique token and create password reset record with OTP
            $token = Str::random(60);
            $passwordReset = PasswordReset::createWithOTP($request->email, $token);
            
            // Send OTP email
            Mail::to($request->email)->send(new PasswordResetOTP($passwordReset->otp, $user->name));
            
            Log::info('Password reset OTP sent via API', [
                'email' => $request->email,
                'user_id' => $user->id,
                'otp' => $passwordReset->otp // For debugging - remove in production
            ]);
            
            return $this->sendResponse([], 'Password reset OTP sent to your email address');
            
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP via API: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
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
            // Validate OTP input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6'
            ], [
                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'otp.required' => 'OTP is required.',
                'otp.size' => 'OTP must be exactly 6 digits.'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Verify OTP using model method
            $passwordReset = PasswordReset::verifyOTP($request->email, $request->otp);
            
            if (!$passwordReset) {
                return $this->sendError('Invalid OTP', ['otp' => 'Invalid or expired OTP. Please try again.'], 400);
            }
            
            // Generate reset token for password reset
            $resetToken = Str::random(64);
            
            Log::info('Password reset OTP verified via API', [
                'email' => $request->email
            ]);
            
            return $this->sendResponse([
                'reset_token' => $resetToken,
                'email' => $request->email,
                'expires_in' => 900 // 15 minutes
            ], 'OTP verified successfully');
            
        } catch (\Exception $e) {
            Log::error('OTP verification failed via API: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
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
            // Validate password input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string'
            ], [
                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'otp.required' => 'OTP is required.',
                'otp.size' => 'OTP must be exactly 6 digits.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters long.',
                'password.confirmed' => 'Password confirmation does not match.',
                'password_confirmation.required' => 'Password confirmation is required.'
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
            
            if (!$user) {
                return $this->sendError('User Not Found', ['email' => 'User not found.'], 404);
            }
            
            // Update user password
            $user->password = Hash::make($request->password);
            $user->save();
            
            // Mark OTP as used and clean up
            $passwordReset->markOTPAsUsed();
            
            // Revoke all existing tokens for security
            $user->tokens()->delete();
            
            Log::info('Password reset successfully via API', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);
            
            return $this->sendResponse([], 'Password reset successfully! Please login with your new password.');
            
        } catch (\Exception $e) {
            Log::error('Password reset failed via API: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
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
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ], [
                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'email.exists' => 'No account found with this email address.'
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
            
            return $this->sendResponse([], 'New OTP has been sent to your email address');
            
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP via API: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Resend Failed', ['error' => 'Unable to resend OTP'], 500);
        }
    }

    /**
     * Send password reset OTP via phone
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPasswordPhone(Request $request): JsonResponse
    {
        try {
            // Validate phone input
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string|exists:users,phone'
            ], [
                'phone.required' => 'Phone number is required.',
                'phone.exists' => 'No account found with this phone number.'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Get user details
            $user = User::where('phone', $request->phone)->first();
            
            if (!$user) {
                return $this->sendError('User Not Found', ['phone' => 'User not found.'], 404);
            }
            
            // Generate unique token and create password reset record with OTP
            $token = Str::random(60);
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
                'message_sid' => $smsResult['message_sid'] ?? null
            ], 'Password reset OTP sent to your phone number');
            
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP via SMS API: ' . $e->getMessage(), [
                'phone' => $request->phone ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Send Failed', ['error' => 'Unable to send password reset OTP'. $e->getMessage()], 500);
        }
    }

    /**
     * Verify password reset OTP for phone
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPhoneOTP(Request $request): JsonResponse
    {
        try {
            // Validate OTP input
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'otp' => 'required|string|size:6'
            ], [
                'phone.required' => 'Phone number is required.',
                'otp.required' => 'OTP is required.',
                'otp.size' => 'OTP must be exactly 6 digits.'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            

            // Verify OTP using model method
            $passwordReset = PasswordReset::verifyPhoneOTP($request->phone, $request->otp);
            
            if (!$passwordReset) {
                return $this->sendError('Invalid OTP', ['otp' => 'Invalid or expired OTP. Please try again.'], 400);
            }
            
            // Generate reset token for password reset
            $resetToken = Str::random(64);
            
            Log::info('Password reset OTP verified via SMS API', [
                'phone' => $request->phone
            ]);
            
            return $this->sendResponse([
                'reset_token' => $resetToken,
                'phone' => $request->phone,
                'expires_in' => 900 // 15 minutes
            ], 'OTP verified successfully');
            
        } catch (\Exception $e) {
            Log::error('Phone OTP verification failed via API: ' . $e->getMessage(), [
                'phone' => $request->phone ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Verification Failed', ['error' => 'Unable to verify OTP'], 500);
        }
    }

    /**
     * Reset password using verified phone OTP
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPasswordPhone(Request $request): JsonResponse
    {
        try {
            // Validate password input
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string'
            ], [
                'phone.required' => 'Phone number is required.',
                'otp.required' => 'OTP is required.',
                'otp.size' => 'OTP must be exactly 6 digits.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters long.',
                'password.confirmed' => 'Password confirmation does not match.',
                'password_confirmation.required' => 'Password confirmation is required.'
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
            
            if (!$user) {
                return $this->sendError('User Not Found', ['phone' => 'User not found.'], 404);
            }
            
            // Update user password
            $user->password = Hash::make($request->password);
            $user->save();
            
            // Mark OTP as used and clean up
            $passwordReset->markOTPAsUsed();
            
            // Revoke all existing tokens for security
            $user->tokens()->delete();
            
            Log::info('Password reset successfully via SMS API', [
                'phone' => $request->phone,
                'user_id' => $user->id
            ]);
            
            return $this->sendResponse([], 'Password reset successfully! Please login with your new password.');
            
        } catch (\Exception $e) {
            Log::error('Password reset via SMS failed: ' . $e->getMessage(), [
                'phone' => $request->phone ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Reset Failed', ['error' => 'Unable to reset password'], 500);
        }
    }

    /**
     * Resend password reset OTP via phone
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendPhoneOTP(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string|exists:users,phone'
            ], [
                'phone.required' => 'Phone number is required.',
                'phone.exists' => 'No account found with this phone number.'
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
                'message_sid' => $smsResult['message_sid'] ?? null
            ], 'New OTP has been sent to your phone number');
            
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP via SMS API: ' . $e->getMessage(), [
                'phone' => $request->phone ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Resend Failed', ['error' => 'Unable to resend OTP'], 500);
        }
    }
}
