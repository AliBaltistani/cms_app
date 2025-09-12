<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

use \Illuminate\Support\Facades\Log;

/**
 * User Profile Controller
 * 
 * Handles user profile management functionality
 * Allows users to view and update their profile information
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    User Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class UserProfileController extends Controller
{
    /**
     * Constructor - Apply auth middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show user profile page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    /**
     * Show profile edit form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update user profile information
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Validate input data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'name.required' => 'Name is required.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already taken.',
            'phone.required' => 'Phone number is required.',
            'phone.unique' => 'This phone number is already taken.',
            'profile_image.image' => 'Profile image must be an image file.',
            'profile_image.mimes' => 'Profile image must be jpeg, png, jpg, or gif.',
            'profile_image.max' => 'Profile image size cannot exceed 2MB.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
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

            // Update user information
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->save();

            return redirect()->route('profile.index')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Profile update failed: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Profile update failed. Please try again.'])->withInput();
        }
    }

    /**
     * Show change password form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function showChangePasswordForm()
    {
        $user = Auth::user();
        return view('profile.change-password', compact('user'));
    }

    /**
     * Update user password
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        
        // Log the password change request for debugging
        Log::info('Password change request received', [
            'user_id' => $user->id
        ]);
        
        // Validate password input
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required'
        ], [
            'current_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Password confirmation is required.'
        ]);

        if ($validator->fails()) {
            Log::warning('Password change validation failed', [
                'user_id' => $user->id,
                'errors' => $validator->errors()->toArray()
            ]);
            return back()->withErrors($validator);
        }

        Log::info('Password change validation passed', ['user_id' => $user->id]);

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        // Check if new password is different from current password
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'New password must be different from current password.']);
        }

        try {
            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            return redirect()->route('profile.index')
                ->with('success', 'Password changed successfully!');

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Password change failed: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Password change failed. Please try again.']);
        }
    }

    /**
     * Delete user profile image
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteProfileImage(Request $request)
    {
        $user = Auth::user();
        
        try {
            // Delete profile image if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
                $user->profile_image = null;
                $user->save();
                
                return back()->with('success', 'Profile image deleted successfully!');
            }
            
            return back()->withErrors(['error' => 'No profile image found to delete.']);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Profile image deletion failed: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to delete profile image. Please try again.']);
        }
    }

    /**
     * Show user activity log (if needed for future implementation)
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function activityLog()
    {
        $user = Auth::user();
        // This can be expanded to show user activity logs
        return view('profile.activity-log', compact('user'));
    }

    /**
     * Show user settings page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function settings()
    {
        $user = Auth::user();
        return view('profile.settings', compact('user'));
    }
}
