<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordReset;
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
                        'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null
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
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'role' => 'nullable|in:admin,trainer,client',
                'device_name' => 'nullable|string|max:255'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
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
                    'profile_image' => null
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
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $user = User::where('email', $request->email)->first();
            
            // Generate OTP
            $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store or update password reset record
            PasswordReset::updateOrCreate(
                ['email' => $request->email],
                [
                    'token' => Hash::make($otp),
                    'otp' => $otp,
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes(15)
                ]
            );
            
            // Send OTP email
            Mail::to($user->email)->send(new PasswordResetOTP($user, $otp));
            
            Log::info('Password reset OTP sent', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);
            
            return $this->sendResponse([], 'Password reset OTP sent to your email');
            
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP: ' . $e->getMessage(), [
                'email' => $request->email,
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
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $passwordReset = PasswordReset::where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('expires_at', '>', now())
                ->first();
            
            if (!$passwordReset) {
                return $this->sendError('Invalid OTP', ['otp' => 'Invalid or expired OTP'], 400);
            }
            
            // Generate reset token for password reset
            $resetToken = Str::random(64);
            $passwordReset->update([
                'reset_token' => Hash::make($resetToken),
                'otp_verified_at' => now()
            ]);
            
            Log::info('Password reset OTP verified', [
                'email' => $request->email
            ]);
            
            return $this->sendResponse([
                'reset_token' => $resetToken,
                'expires_in' => 900 // 15 minutes
            ], 'OTP verified successfully');
            
        } catch (\Exception $e) {
            Log::error('OTP verification failed: ' . $e->getMessage(), [
                'email' => $request->email,
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
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'reset_token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $passwordReset = PasswordReset::where('email', $request->email)
                ->whereNotNull('otp_verified_at')
                ->where('expires_at', '>', now())
                ->first();
            
            if (!$passwordReset || !Hash::check($request->reset_token, $passwordReset->reset_token)) {
                return $this->sendError('Invalid Token', ['reset_token' => 'Invalid or expired reset token'], 400);
            }
            
            // Update user password
            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();
            
            // Delete password reset record
            $passwordReset->delete();
            
            // Revoke all existing tokens for security
            $user->tokens()->delete();
            
            Log::info('Password reset successfully', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);
            
            return $this->sendResponse([], 'Password reset successfully');
            
        } catch (\Exception $e) {
            Log::error('Password reset failed: ' . $e->getMessage(), [
                'email' => $request->email,
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
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Check if there's a recent OTP request (rate limiting)
            $recentReset = PasswordReset::where('email', $request->email)
                ->where('created_at', '>', now()->subMinutes(2))
                ->first();
            
            if ($recentReset) {
                return $this->sendError('Rate Limited', ['error' => 'Please wait before requesting another OTP'], 429);
            }
            
            // Use the same logic as forgotPassword
            return $this->forgotPassword($request);
            
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Resend Failed', ['error' => 'Unable to resend OTP'], 500);
        }
    }
}
