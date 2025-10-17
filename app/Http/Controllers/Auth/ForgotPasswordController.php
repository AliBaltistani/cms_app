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
use App\Services\TwilioSmsService;
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
     * Send OTP to user's email or phone for password reset
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendOTP(Request $request)
    {
        $type = $request->input('type', 'email'); // Default to email for backward compatibility
        
        // Validate input based on type
        if ($type === 'phone') {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string|exists:users,phone',
                'type' => 'required|in:email,phone'
            ], [
                'phone.required' => 'Phone number is required.',
                'phone.exists' => 'No account found with this phone number.',
                'type.in' => 'Invalid reset type specified.'
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'type' => 'sometimes|in:email,phone'
            ], [
                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'email.exists' => 'No account found with this email address.',
                'type.in' => 'Invalid reset type specified.'
            ]);
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Get user details based on type
            if ($type === 'phone') {
                $user = User::where('phone', $request->phone)->first();
                $identifier = $request->phone;
            } else {
                $user = User::where('email', $request->email)->first();
                $identifier = $request->email;
            }
            
            if (!$user) {
                $field = $type === 'phone' ? 'phone' : 'email';
                return back()->withErrors([$field => 'User not found.'])->withInput();
            }

            // Generate unique token and create password reset record with OTP
            $token = Str::random(60);
            
            if ($type === 'phone') {
                $passwordReset = PasswordReset::createWithPhoneOTP($request->phone, $token);
                
                // Send OTP via SMS using TwilioSmsService
                $twilioService = new TwilioSmsService();
                $message = "Your password reset OTP is: {$passwordReset->otp}. This code expires in 15 minutes.";
                $twilioService->sendSms($request->phone, $message);
                
                // Store phone and type in session for OTP verification
                session([
                    'password_reset_phone' => $request->phone,
                    'password_reset_type' => 'phone'
                ]);
                
                $successMessage = 'OTP has been sent to your phone number. Please check your messages.';
            } else {
                $passwordReset = PasswordReset::createWithOTP($request->email, $token);
                
                // Send OTP email
                Mail::to($request->email)->send(new PasswordResetOTP($passwordReset->otp, $user->name));
                
                // Store email and type in session for OTP verification
                session([
                    'password_reset_email' => $request->email,
                    'password_reset_type' => 'email'
                ]);
                
                $successMessage = 'OTP has been sent to your email address. Please check your inbox.';
            }

            return redirect()->route('password.otp.form')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Password reset OTP sending failed: ' . $e->getMessage());
            
            $field = $type === 'phone' ? 'phone' : 'email';
            return back()->withErrors([$field => 'Failed to send OTP. Please try again later.'])->withInput();
        }
    }

    /**
     * Show OTP verification form
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showOTPForm()
    {
        // Check if email or phone is in session
        $hasEmail = session('password_reset_email');
        $hasPhone = session('password_reset_phone');
        
        if (!$hasEmail && !$hasPhone) {
            return redirect()->route('password.request')
                ->withErrors(['error' => 'Session expired. Please request a new OTP.']);
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
        $phone = session('password_reset_phone');
        $type = session('password_reset_type', 'email'); // Default to email for backward compatibility
        
        if (!$email && !$phone) {
            return redirect()->route('password.request')
                ->withErrors(['error' => 'Session expired. Please request a new OTP.']);
        }

        try {
            // Verify OTP based on type
            if ($type === 'phone' && $phone) {
                $passwordReset = PasswordReset::verifyPhoneOTP($phone, $request->otp);
            } else {
                $passwordReset = PasswordReset::verifyOTP($email, $request->otp);
            }

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
        $phone = session('password_reset_phone');
        $type = session('password_reset_type', 'email');
        
        if ((!$email && !$phone) || !session('otp_verified')) {
            return redirect()->route('password.request')
                ->withErrors(['error' => 'Unauthorized access. Please request a new OTP.']);
        }

        try {
            // Find user and update password based on type
            if ($type === 'phone' && $phone) {
                $user = User::where('phone', $phone)->first();
                $identifier = $phone;
            } else {
                $user = User::where('email', $email)->first();
                $identifier = $email;
            }
            
            if (!$user) {
                return back()->withErrors(['error' => 'User not found.']);
            }

            // Update user password
            $user->password = Hash::make($request->password);
            $user->save();

            // Mark OTP as used and clean up based on type
            if ($type === 'phone' && $phone) {
                $passwordReset = PasswordReset::where('phone', $phone)->first();
            } else {
                $passwordReset = PasswordReset::where('email', $email)->first();
            }
            
            if ($passwordReset) {
                $passwordReset->markOTPAsUsed();
            }

            // Clear session data
            session()->forget(['password_reset_email', 'password_reset_phone', 'password_reset_type', 'password_reset_token', 'otp_verified']);

            return redirect()->route('login')
                ->with('success', 'Password reset successfully! Please login with your new password.');

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Password reset failed: ' . $e->getMessage());
            
            return back()->withErrors(['password' => 'Password reset failed. Please try again.']);
        }
    }

    /**
     * Resend OTP to user's email or phone
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendOTP(Request $request)
    {
        $email = session('password_reset_email');
        $phone = session('password_reset_phone');
        $type = session('password_reset_type', 'email');
        
        if (!$email && !$phone) {
            return redirect()->route('password.request')
                ->withErrors(['error' => 'Session expired. Please request a new OTP.']);
        }

        try {
            // Get user details based on type
            if ($type === 'phone' && $phone) {
                $user = User::where('phone', $phone)->first();
                $identifier = $phone;
            } else {
                $user = User::where('email', $email)->first();
                $identifier = $email;
            }
            
            if (!$user) {
                return back()->withErrors(['error' => 'User not found.']);
            }

            // Generate new token and create new password reset record with OTP
            $token = Str::random(60);
            
            if ($type === 'phone' && $phone) {
                $passwordReset = PasswordReset::createWithPhoneOTP($phone, $token);
                
                // Send new OTP via SMS
                $twilioService = new TwilioSmsService();
                $message = "Your password reset OTP is: {$passwordReset->otp}. This code expires in 15 minutes.";
                $twilioService->sendSms($phone, $message);
                
                $successMessage = 'New OTP has been sent to your phone number.';
            } else {
                $passwordReset = PasswordReset::createWithOTP($email, $token);
                
                // Send new OTP email
                Mail::to($email)->send(new PasswordResetOTP($passwordReset->otp, $user->name));
                
                $successMessage = 'New OTP has been sent to your email address.';
            }

            return back()->with('success', $successMessage);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('OTP resend failed: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to resend OTP. Please try again later.']);
        }
    }
}
