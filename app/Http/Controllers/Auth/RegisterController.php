<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

/**
 * Register Controller
 * 
 * Handles user registration and account creation functionality
 * Redirects new users to the dashboard (master.blade.php) after registration
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Auth
 * @category    Authentication
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class RegisterController extends Controller
{

    /**
     * Where to redirect users after registration.
     * Points to the dashboard using master.blade.php layout
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     * Apply guest middleware to prevent authenticated users from accessing registration
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Validate the registration data
        $this->validator($request->all())->validate();

        // Create the new user
        $user = $this->create($request->all());

        // Fire the registered event
        event(new Registered($user));

        // Log the user in automatically after registration
        $this->guard()->login($user);

        // Log successful registration for security audit
        \Illuminate\Support\Facades\Log::info('New user registered and logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString()
        ]);

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'numeric', 'min:10', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            // Custom error messages
            'name.required' => 'Please enter your full name.',
            'name.min' => 'Name must be at least 2 characters long.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        try {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => strtolower(trim($data['phone'])),
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'client', // Default role is 'client'
                'email_verified_at' => now(), // Auto-verify for simplicity
            ]);

            return $user;
        } catch (\Exception $e) {
            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'email' => $data['email'] ?? 'unknown',
                'timestamp' => now()->toDateTimeString()
            ]);

            throw new \Exception('Registration failed. Please try again.');
        }
    }

    /**
     * The user has been registered.
     * Redirect to dashboard which uses master.blade.php layout
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        // Additional setup for new users can be done here
        // For example: creating user profile, sending welcome email, etc.
        
        return redirect()->route('dashboard')->with('success', 'Welcome! Your account has been created successfully.');
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * Get the post-registration redirect path.
     *
     * @return string
     */
    protected function redirectPath()
    {
        return $this->redirectTo;
    }
}