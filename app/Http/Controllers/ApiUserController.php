<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * API User Controller
 * 
 * Handles user profile management operations via API
 * Provides JSON responses for user profile CRUD operations
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\API
 * @category    User Management API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ApiUserController extends ApiBaseController
{
    /**
     * Get authenticated user profile
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Prepare user data for API response
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'business_logo' => $user->business_logo ? asset('storage/' . $user->business_logo) : null,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ];
            
            return $this->sendResponse($userData, 'User profile retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user profile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve profile', ['error' => 'Unable to fetch user profile'], 500);
        }
    }
    
    /**
     * Update authenticated user profile
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Validate input data
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ];

            if ($user->role === 'trainer') {
                $rules['business_logo'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
            }

            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Update user data
            $user->name = $request->name;
            $user->email = $request->email;
            
            if ($request->filled('phone')) {
                $user->phone = $request->phone;
            }
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old profile image if exists
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                
                // Store new profile image
                $imagePath = $request->file('profile_image')->store('profile-images', 'public');
                $user->profile_image = $imagePath;
            }

            // Handle business logo upload
            if ($user->role === 'trainer' && $request->hasFile('business_logo')) {
                if ($user->business_logo && Storage::disk('public')->exists($user->business_logo)) {
                    Storage::disk('public')->delete($user->business_logo);
                }
                $logoPath = $request->file('business_logo')->store('business-logos', 'public');
                $user->business_logo = $logoPath;
            }
            
            $user->save();
            
            // Prepare updated user data for response
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'business_logo' => $user->business_logo ? asset('storage/' . $user->business_logo) : null,
                'updated_at' => $user->updated_at->toISOString(),
            ];
            
            Log::info('User profile updated successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($request->only(['name', 'email', 'phone']))
            ]);
            
            return $this->sendResponse($userData, 'Profile updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to update user profile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['profile_image']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Update Failed', ['error' => 'Unable to update profile'], 500);
        }
    }
    
    /**
     * Change user password
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Validate password change request
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Invalid Password', ['current_password' => 'Current password is incorrect'], 400);
            }
            
            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();
            
            Log::info('User password changed successfully', [
                'user_id' => $user->id,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return $this->sendResponse([], 'Password changed successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to change user password: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Password Change Failed', ['error' => 'Unable to change password'], 500);
        }
    }
    
    /**
     * Upload user avatar
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Validate avatar upload
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Delete old avatar if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            // Store new avatar
            $avatarPath = $request->file('avatar')->store('profile-images', 'public');
            $user->profile_image = $avatarPath;
            $user->save();
            
            $responseData = [
                'profile_image' => asset('storage/' . $avatarPath),
                'updated_at' => $user->updated_at->toISOString()
            ];
            
            Log::info('User avatar uploaded successfully', [
                'user_id' => $user->id,
                'avatar_path' => $avatarPath
            ]);
            
            return $this->sendResponse($responseData, 'Avatar uploaded successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to upload user avatar: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Upload Failed', ['error' => 'Unable to upload avatar'], 500);
        }
    }
    
    /**
     * Delete user avatar
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->profile_image) {
                return $this->sendError('No Avatar', ['error' => 'No avatar to delete'], 400);
            }
            
            // Delete avatar file
            if (Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            // Update user record
            $user->profile_image = null;
            $user->save();
            
            Log::info('User avatar deleted successfully', [
                'user_id' => $user->id
            ]);
            
            return $this->sendResponse([], 'Avatar deleted successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to delete user avatar: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Delete Failed', ['error' => 'Unable to delete avatar'], 500);
        }
    }
    
    /**
     * Get user activity log (placeholder for future implementation)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityLog(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // This is a placeholder - implement actual activity logging as needed
            $activityData = [
                'user_id' => $user->id,
                'activities' => [
                    [
                        'action' => 'profile_viewed',
                        'timestamp' => now()->toISOString(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]
                ],
                'total_activities' => 1
            ];
            
            return $this->sendResponse($activityData, 'Activity log retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve activity log: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve activity log'], 500);
        }
    }
    
    /**
     * Delete user account (soft delete or hard delete based on requirements)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Validate password for account deletion
            $validator = Validator::make($request->all(), [
                'password' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return $this->sendError('Invalid Password', ['password' => 'Password is incorrect'], 400);
            }
            
            // Delete profile image if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            // Revoke all tokens
            $user->tokens()->delete();
            
            Log::warning('User account deleted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'deleted_at' => now()->toDateTimeString()
            ]);
            
            // Delete user account
            $user->delete();
            
            return $this->sendResponse([], 'Account deleted successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to delete user account: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Deletion Failed', ['error' => 'Unable to delete account'], 500);
        }
    }
}