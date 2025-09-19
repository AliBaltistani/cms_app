<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API Documentation Controller
 * 
 * Provides comprehensive API documentation with detailed request/response examples
 * for all available endpoints organized by user roles (Admin, Trainer, Client)
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    API Documentation
 * @author      Go Globe CMS Team
 * @since       2.0.0
 * @version     2.0.0
 * @updated     2025-01-19
 */
class ApiDocumentationController extends Controller
{
    /**
     * Get complete API documentation
     * 
     * Returns comprehensive documentation for all API endpoints
     * organized by user roles and functionality
     * 
     * @param Request $request HTTP request object
     * @return JsonResponse Complete API documentation
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'api_info' => $this->getApiInfo(),
                'authentication' => $this->getAuthenticationDocs(),
                'public_routes' => $this->getPublicRoutesDocs(),
                'common_routes' => $this->getCommonRoutesDocs(),
                'admin_routes' => $this->getAdminRoutesDocs(),
                'trainer_routes' => $this->getTrainerRoutesDocs(),
                'client_routes' => $this->getClientRoutesDocs(),
                'error_responses' => $this->getErrorResponsesDocs(),
                'status_codes' => $this->getStatusCodesDocs()
            ],
            'message' => 'API documentation retrieved successfully'
        ]);
    }

    /**
     * Get API information and overview
     * 
     * @return array API information
     */
    private function getApiInfo(): array
    {
        return [
            'title' => 'Go Globe CMS API Documentation',
            'version' => '2.0.0',
            'description' => 'Complete REST API for Go Globe CMS Application with role-based access control',
            'base_url' => config('app.url') . '/api',
            'content_type' => 'application/json',
            'timezone' => 'Asia/Karachi (PKT)',
            'supported_roles' => ['admin', 'trainer', 'client'],
            'authentication_method' => 'Laravel Sanctum Token-based Authentication',
            'rate_limiting' => 'Applied per user and endpoint',
            'last_updated' => '2025-01-19'
        ];
    }

    /**
     * Get authentication documentation
     * 
     * @return array Authentication documentation
     */
    private function getAuthenticationDocs(): array
    {
        return [
            'overview' => 'All protected routes require Bearer token authentication using Laravel Sanctum',
            'token_header' => 'Authorization: Bearer {your_token_here}',
            'token_expiry' => 'Tokens expire after 24 hours of inactivity',
            'endpoints' => [
                'register' => [
                    'method' => 'POST',
                    'url' => '/api/auth/register',
                    'description' => 'Register a new user account',
                    'authentication' => 'None required',
                    'request_body' => [
                        'name' => 'string|required|max:255',
                        'email' => 'string|required|email|unique:users',
                        'password' => 'string|required|min:8|confirmed',
                        'password_confirmation' => 'string|required',
                        'role' => 'string|required|in:admin,trainer,client',
                        'phone' => 'string|nullable|max:20',
                        'date_of_birth' => 'date|nullable',
                        'gender' => 'string|nullable|in:male,female,other'
                    ],
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'user' => [
                                'id' => 1,
                                'name' => 'John Doe',
                                'email' => 'john@example.com',
                                'role' => 'client',
                                'email_verified_at' => null,
                                'created_at' => '2025-01-19T10:30:00.000000Z'
                            ],
                            'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
                            'token_type' => 'Bearer',
                            'expires_in' => 86400
                        ],
                        'message' => 'User registered successfully'
                    ]
                ],
                'login' => [
                    'method' => 'POST',
                    'url' => '/api/auth/login',
                    'description' => 'Authenticate user and get access token',
                    'authentication' => 'None required',
                    'request_body' => [
                        'email' => 'string|required|email',
                        'password' => 'string|required',
                        'remember_me' => 'boolean|optional'
                    ],
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'user' => [
                                'id' => 1,
                                'name' => 'John Doe',
                                'email' => 'john@example.com',
                                'role' => 'client',
                                'last_login_at' => '2025-01-19T10:30:00.000000Z'
                            ],
                            'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
                            'token_type' => 'Bearer',
                            'expires_in' => 86400
                        ],
                        'message' => 'Login successful'
                    ]
                ],
                'logout' => [
                    'method' => 'POST',
                    'url' => '/api/auth/logout',
                    'description' => 'Logout user and revoke access token',
                    'authentication' => 'Bearer token required',
                    'request_body' => 'No body required',
                    'success_response' => [
                        'success' => true,
                        'data' => null,
                        'message' => 'Logged out successfully'
                    ]
                ],
                'forgot_password' => [
                    'method' => 'POST',
                    'url' => '/api/auth/forgot-password',
                    'description' => 'Request password reset OTP',
                    'authentication' => 'None required',
                    'request_body' => [
                        'email' => 'string|required|email|exists:users'
                    ],
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'otp_sent' => true,
                            'expires_in' => 300
                        ],
                        'message' => 'OTP sent to your email address'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get public routes documentation
     * 
     * @return array Public routes documentation
     */
    private function getPublicRoutesDocs(): array
    {
        return [
            'overview' => 'Public routes accessible without authentication',
            'endpoints' => [
                'system_status' => [
                    'method' => 'GET',
                    'url' => '/api/system/status',
                    'description' => 'Get system status and health information',
                    'authentication' => 'None required',
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'status' => 'online',
                            'version' => '2.0.0',
                            'timestamp' => '2025-01-19T10:30:00.000000Z',
                            'laravel_version' => '10.x'
                        ],
                        'message' => 'System is operational'
                    ]
                ],
                'system_config' => [
                    'method' => 'GET',
                    'url' => '/api/system/config',
                    'description' => 'Get public system configuration',
                    'authentication' => 'None required',
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'app_name' => 'Go Globe CMS',
                            'app_env' => 'production',
                            'timezone' => 'Asia/Karachi',
                            'locale' => 'en'
                        ],
                        'message' => 'System configuration retrieved'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get common authenticated routes documentation
     * 
     * @return array Common routes documentation
     */
    private function getCommonRoutesDocs(): array
    {
        return [
            'overview' => 'Routes accessible to all authenticated users regardless of role',
            'authentication' => 'Bearer token required',
            'endpoints' => [
                'user_profile' => [
                    'method' => 'GET',
                    'url' => '/api/user/profile',
                    'description' => 'Get current user profile information',
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'id' => 1,
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'role' => 'client',
                            'phone' => '+92-300-1234567',
                            'avatar' => 'https://example.com/avatars/user1.jpg',
                            'created_at' => '2025-01-19T10:30:00.000000Z'
                        ],
                        'message' => 'Profile retrieved successfully'
                    ]
                ],
                'update_profile' => [
                    'method' => 'PUT',
                    'url' => '/api/user/profile',
                    'description' => 'Update current user profile',
                    'request_body' => [
                        'name' => 'string|optional|max:255',
                        'phone' => 'string|optional|max:20',
                        'date_of_birth' => 'date|optional',
                        'gender' => 'string|optional|in:male,female,other',
                        'bio' => 'string|optional|max:1000'
                    ],
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'id' => 1,
                            'name' => 'John Doe Updated',
                            'email' => 'john@example.com',
                            'phone' => '+92-300-1234567',
                            'updated_at' => '2025-01-19T10:35:00.000000Z'
                        ],
                        'message' => 'Profile updated successfully'
                    ]
                ],
                'goals_list' => [
                    'method' => 'GET',
                    'url' => '/api/goals',
                    'description' => 'Get user goals with pagination',
                    'query_parameters' => [
                        'page' => 'integer|optional|default:1',
                        'per_page' => 'integer|optional|default:15|max:100',
                        'status' => 'string|optional|in:active,completed,paused',
                        'search' => 'string|optional|max:255'
                    ],
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'goals' => [
                                [
                                    'id' => 1,
                                    'title' => 'Lose 10kg weight',
                                    'description' => 'Target weight loss in 3 months',
                                    'status' => 'active',
                                    'target_date' => '2025-04-19',
                                    'progress' => 30,
                                    'created_at' => '2025-01-19T10:30:00.000000Z'
                                ]
                            ],
                            'pagination' => [
                                'current_page' => 1,
                                'total_pages' => 3,
                                'total_items' => 45,
                                'per_page' => 15
                            ]
                        ],
                        'message' => 'Goals retrieved successfully'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get admin routes documentation
     * 
     * @return array Admin routes documentation
     */
    private function getAdminRoutesDocs(): array
    {
        return [
            'overview' => 'Administrative routes for system management (Admin role required)',
            'authentication' => 'Bearer token required + Admin role',
            'base_prefix' => '/api/admin',
            'endpoints' => [
                'users_management' => [
                    'list_users' => [
                        'method' => 'GET',
                        'url' => '/api/admin/users',
                        'description' => 'Get paginated list of all users',
                        'query_parameters' => [
                            'page' => 'integer|optional|default:1',
                            'per_page' => 'integer|optional|default:15|max:100',
                            'role' => 'string|optional|in:admin,trainer,client',
                            'status' => 'string|optional|in:active,inactive',
                            'search' => 'string|optional|max:255'
                        ],
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'users' => [
                                    [
                                        'id' => 1,
                                        'name' => 'John Doe',
                                        'email' => 'john@example.com',
                                        'role' => 'client',
                                        'status' => 'active',
                                        'last_login_at' => '2025-01-19T10:30:00.000000Z',
                                        'created_at' => '2025-01-19T10:30:00.000000Z'
                                    ]
                                ],
                                'statistics' => [
                                    'total_users' => 150,
                                    'active_users' => 142,
                                    'inactive_users' => 8,
                                    'new_this_month' => 25
                                ]
                            ],
                            'message' => 'Users retrieved successfully'
                        ]
                    ],
                    'create_user' => [
                        'method' => 'POST',
                        'url' => '/api/admin/users',
                        'description' => 'Create a new user account',
                        'request_body' => [
                            'name' => 'string|required|max:255',
                            'email' => 'string|required|email|unique:users',
                            'password' => 'string|required|min:8',
                            'role' => 'string|required|in:admin,trainer,client',
                            'status' => 'string|optional|in:active,inactive|default:active',
                            'phone' => 'string|optional|max:20'
                        ],
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'user' => [
                                    'id' => 151,
                                    'name' => 'New User',
                                    'email' => 'newuser@example.com',
                                    'role' => 'client',
                                    'status' => 'active',
                                    'created_at' => '2025-01-19T10:30:00.000000Z'
                                ]
                            ],
                            'message' => 'User created successfully'
                        ]
                    ]
                ],
                'trainer_management' => [
                    'list_trainers' => [
                        'method' => 'GET',
                        'url' => '/api/admin/trainers',
                        'description' => 'Get list of all trainers with analytics',
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'trainers' => [
                                    [
                                        'id' => 5,
                                        'name' => 'Mike Trainer',
                                        'email' => 'mike@example.com',
                                        'specializations' => ['Weight Loss', 'Strength Training'],
                                        'total_clients' => 25,
                                        'total_workouts' => 45,
                                        'rating' => 4.8,
                                        'status' => 'active'
                                    ]
                                ]
                            ],
                            'message' => 'Trainers retrieved successfully'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get trainer routes documentation
     * 
     * @return array Trainer routes documentation
     */
    private function getTrainerRoutesDocs(): array
    {
        return [
            'overview' => 'Trainer-specific routes for managing workouts, clients, and scheduling (Trainer role required)',
            'authentication' => 'Bearer token required + Trainer role',
            'base_prefix' => '/api/trainer',
            'endpoints' => [
                'profile_management' => [
                    'get_profile' => [
                        'method' => 'GET',
                        'url' => '/api/trainer/profile',
                        'description' => 'Get trainer profile with specializations and certifications',
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'id' => 5,
                                'name' => 'Mike Trainer',
                                'email' => 'mike@example.com',
                                'specializations' => ['Weight Loss', 'Strength Training'],
                                'experience_years' => 8,
                                'bio' => 'Certified personal trainer with 8 years experience',
                                'hourly_rate' => 50.00,
                                'certifications_count' => 3,
                                'clients_count' => 25,
                                'workouts_count' => 45
                            ],
                            'message' => 'Trainer profile retrieved successfully'
                        ]
                    ]
                ],
                'workout_management' => [
                    'create_workout' => [
                        'method' => 'POST',
                        'url' => '/api/trainer/workouts',
                        'description' => 'Create a new workout plan',
                        'request_body' => [
                            'title' => 'string|required|max:255',
                            'description' => 'string|required|max:1000',
                            'difficulty_level' => 'string|required|in:beginner,intermediate,advanced',
                            'duration_minutes' => 'integer|required|min:5|max:180',
                            'category' => 'string|required|max:100',
                            'equipment_needed' => 'array|optional',
                            'target_muscles' => 'array|optional',
                            'is_public' => 'boolean|optional|default:false'
                        ],
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'workout' => [
                                    'id' => 46,
                                    'title' => 'Full Body HIIT Workout',
                                    'description' => 'High intensity interval training for full body',
                                    'difficulty_level' => 'intermediate',
                                    'duration_minutes' => 45,
                                    'category' => 'HIIT',
                                    'trainer_id' => 5,
                                    'is_public' => false,
                                    'created_at' => '2025-01-19T10:30:00.000000Z'
                                ]
                            ],
                            'message' => 'Workout created successfully'
                        ]
                    ],
                    'add_workout_video' => [
                        'method' => 'POST',
                        'url' => '/api/trainer/workouts/{id}/videos',
                        'description' => 'Add video to existing workout',
                        'request_body' => [
                            'title' => 'string|required|max:255',
                            'description' => 'string|optional|max:500',
                            'video_url' => 'string|required|url',
                            'duration_seconds' => 'integer|required|min:1',
                            'order_index' => 'integer|optional',
                            'thumbnail_url' => 'string|optional|url'
                        ],
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'video' => [
                                    'id' => 123,
                                    'title' => 'Warm-up Exercises',
                                    'video_url' => 'https://example.com/videos/warmup.mp4',
                                    'duration_seconds' => 300,
                                    'order_index' => 1,
                                    'workout_id' => 46
                                ]
                            ],
                            'message' => 'Video added to workout successfully'
                        ]
                    ]
                ],
                'scheduling_management' => [
                    'set_availability' => [
                        'method' => 'POST',
                        'url' => '/api/trainer/scheduling/availability',
                        'description' => 'Set trainer availability schedule',
                        'request_body' => [
                            'schedule' => [
                                'monday' => [
                                    'available' => true,
                                    'start_time' => '09:00',
                                    'end_time' => '17:00',
                                    'break_start' => '12:00',
                                    'break_end' => '13:00'
                                ],
                                'tuesday' => [
                                    'available' => true,
                                    'start_time' => '09:00',
                                    'end_time' => '17:00'
                                ]
                            ],
                            'timezone' => 'Asia/Karachi'
                        ],
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'availability_updated' => true,
                                'effective_from' => '2025-01-20T00:00:00.000000Z'
                            ],
                            'message' => 'Availability schedule updated successfully'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get client routes documentation
     * 
     * @return array Client routes documentation
     */
    private function getClientRoutesDocs(): array
    {
        return [
            'overview' => 'Client-specific routes for accessing workouts, booking trainers, and nutrition plans (Client role required)',
            'authentication' => 'Bearer token required + Client role',
            'base_prefix' => '/api/client',
            'endpoints' => [
                'dashboard' => [
                    'method' => 'GET',
                    'url' => '/api/client/dashboard',
                    'description' => 'Get client dashboard with overview statistics',
                    'success_response' => [
                        'success' => true,
                        'data' => [
                            'overview' => [
                                'total_workouts' => 15,
                                'completed_workouts' => 8,
                                'active_goals' => 3,
                                'upcoming_sessions' => 2
                            ],
                            'recent_workouts' => [
                                [
                                    'id' => 46,
                                    'title' => 'Full Body HIIT Workout',
                                    'duration_minutes' => 45,
                                    'completed_at' => '2025-01-18T16:30:00.000000Z'
                                ]
                            ],
                            'upcoming_sessions' => [
                                [
                                    'id' => 25,
                                    'trainer_name' => 'Mike Trainer',
                                    'scheduled_at' => '2025-01-20T10:00:00.000000Z',
                                    'duration_minutes' => 60,
                                    'status' => 'confirmed'
                                ]
                            ]
                        ],
                        'message' => 'Dashboard data retrieved successfully'
                    ]
                ],
                'workout_access' => [
                    'list_workouts' => [
                        'method' => 'GET',
                        'url' => '/api/client/workouts',
                        'description' => 'Get available workouts for client',
                        'query_parameters' => [
                            'category' => 'string|optional',
                            'difficulty' => 'string|optional|in:beginner,intermediate,advanced',
                            'duration_min' => 'integer|optional',
                            'duration_max' => 'integer|optional',
                            'search' => 'string|optional'
                        ],
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'workouts' => [
                                    [
                                        'id' => 46,
                                        'title' => 'Full Body HIIT Workout',
                                        'description' => 'High intensity interval training',
                                        'difficulty_level' => 'intermediate',
                                        'duration_minutes' => 45,
                                        'category' => 'HIIT',
                                        'trainer' => [
                                            'id' => 5,
                                            'name' => 'Mike Trainer'
                                        ],
                                        'videos_count' => 8,
                                        'rating' => 4.7
                                    ]
                                ]
                            ],
                            'message' => 'Workouts retrieved successfully'
                        ]
                    ]
                ],
                'trainer_discovery' => [
                    'find_trainers' => [
                        'method' => 'GET',
                        'url' => '/api/client/trainers/find',
                        'description' => 'Find available trainers based on criteria',
                        'query_parameters' => [
                            'specialization' => 'string|optional',
                            'min_rating' => 'numeric|optional|min:1|max:5',
                            'max_hourly_rate' => 'numeric|optional',
                            'availability_date' => 'date|optional',
                            'location' => 'string|optional'
                        ],
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'trainers' => [
                                    [
                                        'id' => 5,
                                        'name' => 'Mike Trainer',
                                        'specializations' => ['Weight Loss', 'Strength Training'],
                                        'experience_years' => 8,
                                        'hourly_rate' => 50.00,
                                        'rating' => 4.8,
                                        'total_clients' => 25,
                                        'available_slots' => 12,
                                        'next_available' => '2025-01-20T09:00:00.000000Z'
                                    ]
                                ]
                            ],
                            'message' => 'Trainers found successfully'
                        ]
                    ]
                ],
                'booking_management' => [
                    'request_booking' => [
                        'method' => 'POST',
                        'url' => '/api/client/bookings',
                        'description' => 'Request a booking with a trainer',
                        'request_body' => [
                            'trainer_id' => 'integer|required|exists:users,id',
                            'session_date' => 'date|required|after:today',
                            'session_time' => 'string|required|date_format:H:i',
                            'duration_minutes' => 'integer|required|in:30,60,90,120',
                            'session_type' => 'string|required|in:personal,group',
                            'notes' => 'string|optional|max:500'
                        ],
                        'success_response' => [
                            'success' => true,
                            'data' => [
                                'booking' => [
                                    'id' => 26,
                                    'trainer_id' => 5,
                                    'client_id' => 1,
                                    'session_date' => '2025-01-22',
                                    'session_time' => '10:00',
                                    'duration_minutes' => 60,
                                    'status' => 'pending',
                                    'total_cost' => 50.00,
                                    'created_at' => '2025-01-19T10:30:00.000000Z'
                                ]
                            ],
                            'message' => 'Booking request submitted successfully'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get error responses documentation
     * 
     * @return array Error responses documentation
     */
    private function getErrorResponsesDocs(): array
    {
        return [
            'overview' => 'Standard error response formats used across all API endpoints',
            'error_structure' => [
                'success' => false,
                'message' => 'Human-readable error message',
                'errors' => [
                    'field_name' => ['Specific validation error message']
                ],
                'error_code' => 'SPECIFIC_ERROR_CODE',
                'timestamp' => '2025-01-19T10:30:00.000000Z'
            ],
            'common_errors' => [
                'validation_error' => [
                    'status_code' => 422,
                    'example' => [
                        'success' => false,
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'email' => ['The email field is required.'],
                            'password' => ['The password must be at least 8 characters.']
                        ],
                        'error_code' => 'VALIDATION_ERROR'
                    ]
                ],
                'authentication_error' => [
                    'status_code' => 401,
                    'example' => [
                        'success' => false,
                        'message' => 'Unauthenticated. Please provide a valid token.',
                        'error_code' => 'AUTHENTICATION_REQUIRED'
                    ]
                ],
                'authorization_error' => [
                    'status_code' => 403,
                    'example' => [
                        'success' => false,
                        'message' => 'Access denied. Insufficient permissions.',
                        'error_code' => 'INSUFFICIENT_PERMISSIONS'
                    ]
                ],
                'not_found_error' => [
                    'status_code' => 404,
                    'example' => [
                        'success' => false,
                        'message' => 'The requested resource was not found.',
                        'error_code' => 'RESOURCE_NOT_FOUND'
                    ]
                ],
                'server_error' => [
                    'status_code' => 500,
                    'example' => [
                        'success' => false,
                        'message' => 'An internal server error occurred. Please try again later.',
                        'error_code' => 'INTERNAL_SERVER_ERROR'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get HTTP status codes documentation
     * 
     * @return array Status codes documentation
     */
    private function getStatusCodesDocs(): array
    {
        return [
            'success_codes' => [
                200 => 'OK - Request successful',
                201 => 'Created - Resource created successfully',
                202 => 'Accepted - Request accepted for processing',
                204 => 'No Content - Request successful, no content to return'
            ],
            'client_error_codes' => [
                400 => 'Bad Request - Invalid request format or parameters',
                401 => 'Unauthorized - Authentication required or invalid token',
                403 => 'Forbidden - Access denied, insufficient permissions',
                404 => 'Not Found - Requested resource does not exist',
                422 => 'Unprocessable Entity - Validation errors in request data',
                429 => 'Too Many Requests - Rate limit exceeded'
            ],
            'server_error_codes' => [
                500 => 'Internal Server Error - Unexpected server error',
                502 => 'Bad Gateway - Invalid response from upstream server',
                503 => 'Service Unavailable - Server temporarily unavailable',
                504 => 'Gateway Timeout - Request timeout from upstream server'
            ]
        ];
    }

    /**
     * Get specific endpoint documentation
     * 
     * @param Request $request HTTP request object
     * @param string $endpoint Endpoint name
     * @return JsonResponse Specific endpoint documentation
     */
    public function getEndpoint(Request $request, string $endpoint): JsonResponse
    {
        $endpoints = [
            'auth' => $this->getAuthenticationDocs(),
            'public' => $this->getPublicRoutesDocs(),
            'common' => $this->getCommonRoutesDocs(),
            'admin' => $this->getAdminRoutesDocs(),
            'trainer' => $this->getTrainerRoutesDocs(),
            'client' => $this->getClientRoutesDocs()
        ];

        if (!isset($endpoints[$endpoint])) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint documentation not found',
                'available_endpoints' => array_keys($endpoints)
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $endpoints[$endpoint],
            'message' => ucfirst($endpoint) . ' endpoint documentation retrieved successfully'
        ]);
    }

    /**
     * Get API schema for OpenAPI/Swagger
     * 
     * @param Request $request HTTP request object
     * @return JsonResponse OpenAPI schema
     */
    public function getSchema(Request $request): JsonResponse
    {
        return response()->json([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Go Globe CMS API',
                'version' => '2.0.0',
                'description' => 'Complete REST API for Go Globe CMS Application',
                'contact' => [
                    'name' => 'Go Globe CMS Team',
                    'email' => 'support@goglobe.com'
                ]
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api',
                    'description' => 'Production server'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ]
            ],
            'security' => [
                ['bearerAuth' => []]
            ]
        ]);
    }
}