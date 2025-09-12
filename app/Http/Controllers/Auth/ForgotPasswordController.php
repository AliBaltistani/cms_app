<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\PasswordReset;
use App\Mail\PasswordResetOTP;
use Carbon\Carbon;

use \Illuminate\Support\Facades\Log;

/**
 * Forgot Password Controller
 * 
 * Handles password reset functionality with OTP verification
 * Sends OTP via email and validates reset requests
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Auth
 * @category    Authentication
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class ForgotPasswordController extends Controller
{
    /**
     * Constructor - Apply guest middleware
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the forgot password form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send OTP to user's email for password reset
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendOTP(Request $request)
    {
        // Validate email input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.exists' => 'No account found with this email address.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Get user details
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return back()->withErrors(['email' => 'User not found.'])->withInput();
            }

            // Generate unique token and create password reset record with OTP
            $token = Str::random(60);
            $passwordReset = PasswordReset::createWithOTP($request->email, $token);

            // Send OTP email
            Mail::to($request->email)->send(new PasswordResetOTP($passwordReset->otp, $user->name));

            // Store email in session for OTP verification
            session(['password_reset_email' => $request->email]);

            return redirect()->route('password.otp.form')
                ->with('success', 'OTP has been sent to your email address. Please check your inbox.');

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Password reset OTP sending failed: ' . $e->getMessage());
            
            return back()->withErrors(['email' => 'Failed to send OTP. Please try again later.'])->withInput();
        }
    }

    /**
     * Show OTP verification form
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showOTPForm()
    {
        // Check if email is in session
        if (!session('password_reset_email')) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Session expired. Please request a new OTP.']);
        }

        return view('auth.verify-otp');
    }

    /**
     * Verify OTP and show password reset form
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyOTP(Request $request)
    {
        // Validate OTP input
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6'
        ], [
            'otp.required' => 'OTP is required.',
            'otp.size' => 'OTP must be exactly 6 digits.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = session('password_reset_email');
        
        if (!$email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Session expired. Please request a new OTP.']);
        }

        try {
            // Verify OTP
            $passwordReset = PasswordReset::verifyOTP($email, $request->otp);

            if (!$passwordReset) {
                return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.'])->withInput();
            }

            // Store verified token in session for password reset
            session(['password_reset_token' => $passwordReset->token, 'otp_verified' => true]);

            return redirect()->route('password.reset.form')
                ->with('success', 'OTP verified successfully. Please set your new password.');

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('OTP verification failed: ' . $e->getMessage());
            
            return back()->withErrors(['otp' => 'OTP verification failed. Please try again.'])->withInput();
        }
    }

    /**
     * Show password reset form
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showResetForm()
    {
        // Check if OTP is verified
        if (!session('otp_verified') || !session('password_reset_email')) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Unauthorized access. Please request a new OTP.']);
        }

        return view('auth.reset-password');
    }

    /**
     * Reset user password
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        // Validate password input
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required'
        ], [
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Password confirmation is required.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = session('password_reset_email');
        
        if (!$email || !session('otp_verified')) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Unauthorized access. Please request a new OTP.']);
        }

        try {
            // Find user and update password
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return back()->withErrors(['email' => 'User not found.']);
            }

            // Update user password
            $user->password = Hash::make($request->password);
            $user->save();

            // Mark OTP as used and clean up
            $passwordReset = PasswordReset::where('email', $email)->first();
            if ($passwordReset) {
                $passwordReset->markOTPAsUsed();
            }

            // Clear session data
            session()->forget(['password_reset_email', 'password_reset_token', 'otp_verified']);

            return redirect()->route('login')
                ->with('success', 'Password reset successfully! Please login with your new password.');

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Password reset failed: ' . $e->getMessage());
            
            return back()->withErrors(['password' => 'Password reset failed. Please try again.']);
        }
    }

    /**
     * Resend OTP to user's email
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendOTP(Request $request)
    {
        $email = session('password_reset_email');
        
        if (!$email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Session expired. Please request a new OTP.']);
        }

        try {
            // Get user details
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return back()->withErrors(['email' => 'User not found.']);
            }

            // Generate new token and create new password reset record with OTP
            $token = Str::random(60);
            $passwordReset = PasswordReset::createWithOTP($email, $token);

            // Send new OTP email
            Mail::to($email)->send(new PasswordResetOTP($passwordReset->otp, $user->name));

            return back()->with('success', 'New OTP has been sent to your email address.');

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('OTP resend failed: ' . $e->getMessage());
            
            return back()->withErrors(['email' => 'Failed to resend OTP. Please try again later.']);
        }
    }
}
