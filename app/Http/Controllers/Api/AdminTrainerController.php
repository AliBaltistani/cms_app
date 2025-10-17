<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\Testimonial;
use App\Models\TestimonialLikesDislike;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Admin Trainer API Controller
 * 
 * Handles trainer-specific operations via API for admin users
 * Manages trainer profiles, certifications, and testimonials
 * All endpoints require admin authentication
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Api
 * @category    Trainer Management API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class AdminTrainerController extends ApiBaseController
{
    /**
     * Get all trainers with detailed information and statistics
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:active,inactive',
                'experience' => 'nullable|in:beginner,intermediate,expert',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
                'sort_by' => 'nullable|in:id,name,email,experience,created_at,average_rating',
                'sort_order' => 'nullable|in:asc,desc'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Build query for trainers only
            $query = User::where('role', 'trainer')
                        ->with(['receivedTestimonials', 'certifications', 'workouts']);
            
            // Apply status filter
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }
            
            // Apply experience filter
            if ($request->filled('experience')) {
                switch ($request->experience) {
                    case 'beginner':
                        $query->where('experience', '<=', 2);
                        break;
                    case 'intermediate':
                        $query->whereBetween('experience', [3, 7]);
                        break;
                    case 'expert':
                        $query->where('experience', '>=', 8);
                        break;
                }
            }
            
            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('designation', 'like', "%{$search}%")
                      ->orWhere('about', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'average_rating') {
                // Special handling for average rating sorting
                $query->leftJoin('testimonials', 'users.id', '=', 'testimonials.trainer_id')
                      ->select('users.*', DB::raw('AVG(testimonials.rate) as avg_rating'))
                      ->groupBy('users.id')
                      ->orderBy('avg_rating', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
            
            // Get paginated results
            $perPage = $request->get('per_page', 20);
            $trainers = $query->paginate($perPage);
            
            // Transform trainer data with basic information
            $transformedTrainers = $trainers->getCollection()->map(function($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'designation' => $trainer->designation ?? 'Personal Trainer',
                    'profile_image' => $trainer->profile_image ? asset('storage/' . $trainer->profile_image) : null
                ];
            });
            
            // Prepare response data
            $responseData = [
                'trainers' => $transformedTrainers,
                'pagination' => [
                    'current_page' => $trainers->currentPage(),
                    'last_page' => $trainers->lastPage(),
                    'per_page' => $trainers->perPage(),
                    'total' => $trainers->total(),
                    'from' => $trainers->firstItem(),
                    'to' => $trainers->lastItem()
                ],
                'statistics' => [
                    'total_trainers' => User::where('role', 'trainer')->count(),
                    'active_trainers' => User::where('role', 'trainer')->whereNotNull('email_verified_at')->count(),
                    'inactive_trainers' => User::where('role', 'trainer')->whereNull('email_verified_at')->count(),
                    'total_certifications' => UserCertification::count(),
                    'total_testimonials' => Testimonial::count(),
                    'average_experience' => User::where('role', 'trainer')->avg('experience') ?? 0,
                    'average_rating' => Testimonial::avg('rate') ?? 0
                ]
            ];
            
            return $this->sendResponse($responseData, 'Trainers retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainers via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve trainers', ['error' => 'Unable to fetch trainers'], 500);
        }
    }
    
    /**
     * Get a specific trainer with detailed information
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')
                          ->with([
                              'receivedTestimonials.client',
                              'certifications',
                              'workouts'
                          ])
                          ->find($id);
            
            if (!$trainer) {
                return $this->sendError('Trainer not found', ['error' => 'Trainer does not exist'], 404);
            }
            
            // Calculate trainer statistics
            $avgRating = $trainer->receivedTestimonials->avg('rate') ?? 0;
            $ratingDistribution = $trainer->receivedTestimonials->groupBy('rate')
                ->map(function($group) {
                    return $group->count();
                })->toArray();
            
            // Transform trainer data with detailed information
            $transformedTrainer = [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'email' => $trainer->email,
                'phone' => $trainer->phone,
                'designation' => $trainer->designation,
                'experience' => $trainer->experience,
                'about' => $trainer->about,
                'training_philosophy' => $trainer->training_philosophy,
                'status' => $trainer->email_verified_at ? 'active' : 'inactive',
                'profile_image' => $trainer->profile_image ? asset('storage/' . $trainer->profile_image) : null,
                'email_verified_at' => $trainer->email_verified_at,
                'created_at' => $trainer->created_at->toISOString(),
                'updated_at' => $trainer->updated_at->toISOString(),
                'statistics' => [
                    'certifications_count' => $trainer->certifications->count(),
                    'testimonials_count' => $trainer->receivedTestimonials->count(),
                    'workouts_count' => $trainer->workouts->count(),
                    'average_rating' => round($avgRating, 1),
                    'total_likes' => $trainer->receivedTestimonials->sum('likes'),
                    'experience_level' => $this->getExperienceLevel($trainer->experience),
                    'rating_distribution' => $ratingDistribution
                ],
                'certifications' => $trainer->certifications->map(function($cert) {
                    return [
                        'id' => $cert->id,
                        'name' => $cert->name,
                        'issuing_organization' => $cert->issuing_organization,
                        'issue_date' => $cert->issue_date,
                        'expiry_date' => $cert->expiry_date,
                        'credential_id' => $cert->credential_id,
                        'credential_url' => $cert->credential_url,
                        'is_expired' => $cert->expiry_date ? now()->gt($cert->expiry_date) : false,
                        'created_at' => $cert->created_at->toISOString()
                    ];
                }),
                'recent_testimonials' => $trainer->receivedTestimonials->take(10)->map(function($testimonial) {
                    return [
                        'id' => $testimonial->id,
                        'content' => $testimonial->content,
                        'rate' => $testimonial->rate,
                        'likes' => $testimonial->likes,
                        'client' => [
                            'id' => $testimonial->client->id,
                            'name' => $testimonial->client->name,
                            'profile_image' => $testimonial->client->profile_image ? asset('storage/' . $testimonial->client->profile_image) : null
                        ],
                        'created_at' => $testimonial->created_at->toISOString()
                    ];
                }),
                'recent_workouts' => $trainer->workouts->take(5)->map(function($workout) {
                    return [
                        'id' => $workout->id,
                        'name' => $workout->name,
                        'description' => $workout->description,
                        'duration' => $workout->duration,
                        'difficulty_level' => $workout->difficulty_level,
                        'created_at' => $workout->created_at->toISOString()
                    ];
                })
            ];
            
            return $this->sendResponse($transformedTrainer, 'Trainer retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainer via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve trainer', ['error' => 'Unable to fetch trainer details'], 500);
        }
    }
    
    /**
     * Get trainer certifications
     * 
     * @param  int  $trainerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCertifications($trainerId): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->find($trainerId);
            
            if (!$trainer) {
                return $this->sendError('Trainer not found', ['error' => 'Trainer does not exist'], 404);
            }
            
            $certifications = UserCertification::where('user_id', $trainer->id)
                                              ->orderBy('issue_date', 'desc')
                                              ->get()
                                              ->map(function($cert) {
                                                  return [
                                                      'id' => $cert->id,
                                                      'name' => $cert->name,
                                                      'issuing_organization' => $cert->issuing_organization,
                                                      'issue_date' => $cert->issue_date,
                                                      'expiry_date' => $cert->expiry_date,
                                                      'credential_id' => $cert->credential_id,
                                                      'credential_url' => $cert->credential_url,
                                                      'is_expired' => $cert->expiry_date ? now()->gt($cert->expiry_date) : false,
                                                      'days_until_expiry' => $cert->expiry_date ? now()->diffInDays($cert->expiry_date, false) : null,
                                                      'created_at' => $cert->created_at->toISOString(),
                                                      'updated_at' => $cert->updated_at->toISOString()
                                                  ];
                                              });
            
            $responseData = [
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email
                ],
                'certifications' => $certifications,
                'statistics' => [
                    'total_certifications' => $certifications->count(),
                    'active_certifications' => $certifications->where('is_expired', false)->count(),
                    'expired_certifications' => $certifications->where('is_expired', true)->count(),
                    'expiring_soon' => $certifications->filter(function($cert) {
                        return $cert['days_until_expiry'] !== null && $cert['days_until_expiry'] <= 30 && $cert['days_until_expiry'] > 0;
                    })->count()
                ]
            ];
            
            return $this->sendResponse($responseData, 'Trainer certifications retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainer certifications via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve certifications', ['error' => 'Unable to fetch certifications'], 500);
        }
    }
    
    /**
     * Add a new certification for trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $trainerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCertification(Request $request, $trainerId): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->find($trainerId);
            
            if (!$trainer) {
                return $this->sendError('Trainer not found', ['error' => 'Trainer does not exist'], 404);
            }
            
            // Validate certification data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'issuing_organization' => 'required|string|max:255',
                'issue_date' => 'required|date|before_or_equal:today',
                'expiry_date' => 'nullable|date|after:issue_date',
                'credential_id' => 'nullable|string|max:255',
                'credential_url' => 'nullable|url|max:500'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Create certification
            $certification = UserCertification::create([
                'user_id' => $trainer->id,
                'name' => $request->name,
                'issuing_organization' => $request->issuing_organization,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'credential_id' => $request->credential_id,
                'credential_url' => $request->credential_url
            ]);
            
            // Transform certification data for response
            $transformedCertification = [
                'id' => $certification->id,
                'name' => $certification->name,
                'issuing_organization' => $certification->issuing_organization,
                'issue_date' => $certification->issue_date,
                'expiry_date' => $certification->expiry_date,
                'credential_id' => $certification->credential_id,
                'credential_url' => $certification->credential_url,
                'is_expired' => $certification->expiry_date ? now()->gt($certification->expiry_date) : false,
                'created_at' => $certification->created_at->toISOString()
            ];
            
            Log::info('Certification added to trainer via API by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'certification_id' => $certification->id
            ]);
            
            return $this->sendResponse($transformedCertification, 'Certification added successfully', 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to add certification via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to add certification'.$e->getMessage(), ['error' => 'Unable to create certification'], 500);
        }
    }
    
    /**
     * Update a trainer certification
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $trainerId
     * @param  int  $certificationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCertification(Request $request, $trainerId, $certificationId): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->find($trainerId);
            
            if (!$trainer) {
                return $this->sendError('Trainer not found', ['error' => 'Trainer does not exist'], 404);
            }
            
            $certification = UserCertification::where('user_id', $trainer->id)->find($certificationId);
            
            if (!$certification) {
                return $this->sendError('Certification not found', ['error' => 'Certification does not exist'], 404);
            }
            
            // Validate certification data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'issuing_organization' => 'required|string|max:255',
                'issue_date' => 'required|date|before_or_equal:today',
                'expiry_date' => 'nullable|date|after:issue_date',
                'credential_id' => 'nullable|string|max:255',
                'credential_url' => 'nullable|url|max:500'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Update certification
            $certification->update([
                'name' => $request->name,
                'issuing_organization' => $request->issuing_organization,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'credential_id' => $request->credential_id,
                'credential_url' => $request->credential_url
            ]);
            
            // Transform certification data for response
            $transformedCertification = [
                'id' => $certification->id,
                'name' => $certification->name,
                'issuing_organization' => $certification->issuing_organization,
                'issue_date' => $certification->issue_date,
                'expiry_date' => $certification->expiry_date,
                'credential_id' => $certification->credential_id,
                'credential_url' => $certification->credential_url,
                'is_expired' => $certification->expiry_date ? now()->gt($certification->expiry_date) : false,
                'updated_at' => $certification->updated_at->toISOString()
            ];
            
            Log::info('Certification updated via API by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'certification_id' => $certification->id
            ]);
            
            return $this->sendResponse($transformedCertification, 'Certification updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to update certification via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'certification_id' => $certificationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to update certification', ['error' => 'Unable to update certification'], 500);
        }
    }
    
    /**
     * Delete a trainer certification
     * 
     * @param  int  $trainerId
     * @param  int  $certificationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCertification($trainerId, $certificationId): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->find($trainerId);
            
            if (!$trainer) {
                return $this->sendError('Trainer not found', ['error' => 'Trainer does not exist'], 404);
            }
            
            $certification = UserCertification::where('user_id', $trainer->id)->find($certificationId);
            
            if (!$certification) {
                return $this->sendError('Certification not found', ['error' => 'Certification does not exist'], 404);
            }
            
            Log::info('Certification deleted via API by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'certification_id' => $certification->id,
                'certification_name' => $certification->name
            ]);
            
            $certification->delete();
            
            return $this->sendResponse([], 'Certification deleted successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to delete certification via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'certification_id' => $certificationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to delete certification', ['error' => 'Unable to delete certification'], 500);
        }
    }
    
    /**
     * Get trainer testimonials with statistics
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $trainerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTestimonials(Request $request, $trainerId): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->find($trainerId);
            
            if (!$trainer) {
                return $this->sendError('Trainer not found', ['error' => 'Trainer does not exist'], 404);
            }
            
            // Validate query parameters
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'rating' => 'nullable|integer|min:1|max:5',
                'sort_by' => 'nullable|in:created_at,rate,likes',
                'sort_order' => 'nullable|in:asc,desc'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Build testimonials query
            $query = Testimonial::where('trainer_id', $trainer->id)
                               ->with('client');
            
            // Apply rating filter
            if ($request->filled('rating')) {
                $query->where('rate', $request->rating);
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Get paginated results
            $perPage = $request->get('per_page', 20);
            $testimonials = $query->paginate($perPage);
            
            // Transform testimonials data
            $transformedTestimonials = $testimonials->getCollection()->map(function($testimonial) {
                return [
                    'id' => $testimonial->id,
                    'content' => $testimonial->content,
                    'rate' => $testimonial->rate,
                    'likes' => $testimonial->likes,
                    'client' => [
                        'id' => $testimonial->client->id,
                        'name' => $testimonial->client->name,
                        'email' => $testimonial->client->email,
                        'profile_image' => $testimonial->client->profile_image ? asset('storage/' . $testimonial->client->profile_image) : null
                    ],
                    'created_at' => $testimonial->created_at->toISOString(),
                    'updated_at' => $testimonial->updated_at->toISOString()
                ];
            });
            
            // Calculate statistics
            $allTestimonials = Testimonial::where('trainer_id', $trainer->id)->get();
            $ratingDistribution = $allTestimonials->groupBy('rate')
                ->map(function($group) {
                    return $group->count();
                })->toArray();
            
            $responseData = [
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email
                ],
                'testimonials' => $transformedTestimonials,
                'pagination' => [
                    'current_page' => $testimonials->currentPage(),
                    'last_page' => $testimonials->lastPage(),
                    'per_page' => $testimonials->perPage(),
                    'total' => $testimonials->total(),
                    'from' => $testimonials->firstItem(),
                    'to' => $testimonials->lastItem()
                ],
                'statistics' => [
                    'total_testimonials' => $allTestimonials->count(),
                    'average_rating' => round($allTestimonials->avg('rate') ?? 0, 1),
                    'total_likes' => $allTestimonials->sum('likes'),
                    'rating_distribution' => $ratingDistribution,
                    'recent_testimonials_count' => $allTestimonials->where('created_at', '>=', now()->subDays(30))->count()
                ]
            ];
            
            return $this->sendResponse($responseData, 'Trainer testimonials retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainer testimonials via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve testimonials', ['error' => 'Unable to fetch testimonials'], 500);
        }
    }
    
    /**
     * Toggle trainer status (activate/deactivate)
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->find($id);
            
            if (!$trainer) {
                return $this->sendError('Trainer not found', ['error' => 'Trainer does not exist'], 404);
            }
            
            // Toggle email verification status
            $trainer->email_verified_at = $trainer->email_verified_at ? null : now();
            $trainer->save();
            
            Log::info('Trainer status toggled via API by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'new_status' => $trainer->email_verified_at ? 'active' : 'inactive'
            ]);
            
            $responseData = [
                'trainer_id' => $trainer->id,
                'status' => $trainer->email_verified_at ? 'active' : 'inactive',
                'email_verified_at' => $trainer->email_verified_at
            ];
            
            return $this->sendResponse($responseData, 'Trainer status updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle trainer status via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to update trainer status', ['error' => 'Unable to toggle trainer status'], 500);
        }
    }
    
    /**
     * Get trainer analytics and insights
     * 
     * @param  int  $trainerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAnalytics($trainerId): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->find($trainerId);
            
            if (!$trainer) {
                return $this->sendError('Trainer not found', ['error' => 'Trainer does not exist'], 404);
            }
            
            // Get testimonials for analysis
            $testimonials = Testimonial::where('trainer_id', $trainer->id)->get();
            
            // Calculate monthly testimonial trends
            $monthlyTestimonials = $testimonials->groupBy(function($testimonial) {
                return $testimonial->created_at->format('Y-m');
            })->map(function($group) {
                return [
                    'count' => $group->count(),
                    'average_rating' => round($group->avg('rate'), 1),
                    'total_likes' => $group->sum('likes')
                ];
            });
            
            // Get certification expiry alerts
            $certifications = UserCertification::where('user_id', $trainer->id)->get();
            $expiringCertifications = $certifications->filter(function($cert) {
                return $cert->expiry_date && now()->diffInDays($cert->expiry_date, false) <= 30 && now()->diffInDays($cert->expiry_date, false) > 0;
            });
            
            $analytics = [
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'experience_level' => $this->getExperienceLevel($trainer->experience)
                ],
                'performance_metrics' => [
                    'total_testimonials' => $testimonials->count(),
                    'average_rating' => round($testimonials->avg('rate') ?? 0, 1),
                    'total_likes' => $testimonials->sum('likes'),
                    'rating_trend' => $this->calculateRatingTrend($testimonials),
                    'client_satisfaction' => $this->calculateClientSatisfaction($testimonials)
                ],
                'monthly_trends' => $monthlyTestimonials,
                'certifications_status' => [
                    'total' => $certifications->count(),
                    'active' => $certifications->filter(function($cert) {
                        return !$cert->expiry_date || now()->lt($cert->expiry_date);
                    })->count(),
                    'expired' => $certifications->filter(function($cert) {
                        return $cert->expiry_date && now()->gt($cert->expiry_date);
                    })->count(),
                    'expiring_soon' => $expiringCertifications->count()
                ],
                'alerts' => [
                    'expiring_certifications' => $expiringCertifications->map(function($cert) {
                        return [
                            'id' => $cert->id,
                            'name' => $cert->name,
                            'expiry_date' => $cert->expiry_date,
                            'days_until_expiry' => now()->diffInDays($cert->expiry_date, false)
                        ];
                    })
                ],
                'recommendations' => $this->generateRecommendations($trainer, $testimonials, $certifications)
            ];
            
            return $this->sendResponse($analytics, 'Trainer analytics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainer analytics via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve analytics', ['error' => 'Unable to fetch trainer analytics'], 500);
        }
    }
    
    /**
     * Get experience level based on years of experience
     * 
     * @param  int|null  $experience
     * @return string
     */
    private function getExperienceLevel($experience): string
    {
        if (!$experience) return 'Not specified';
        
        if ($experience <= 2) return 'Beginner';
        if ($experience <= 7) return 'Intermediate';
        return 'Expert';
    }
    
    /**
     * Calculate rating trend
     * 
     * @param  \Illuminate\Support\Collection  $testimonials
     * @return string
     */
    private function calculateRatingTrend($testimonials): string
    {
        if ($testimonials->count() < 2) return 'insufficient_data';
        
        $recent = $testimonials->where('created_at', '>=', now()->subDays(30))->avg('rate') ?? 0;
        $previous = $testimonials->where('created_at', '<', now()->subDays(30))->avg('rate') ?? 0;
        
        if ($recent > $previous) return 'improving';
        if ($recent < $previous) return 'declining';
        return 'stable';
    }
    
    /**
     * Calculate client satisfaction percentage
     * 
     * @param  \Illuminate\Support\Collection  $testimonials
     * @return float
     */
    private function calculateClientSatisfaction($testimonials): float
    {
        if ($testimonials->count() === 0) return 0;
        
        $satisfiedClients = $testimonials->where('rate', '>=', 4)->count();
        return round(($satisfiedClients / $testimonials->count()) * 100, 1);
    }
    
    /**
     * Generate recommendations for trainer improvement
     * 
     * @param  \App\Models\User  $trainer
     * @param  \Illuminate\Support\Collection  $testimonials
     * @param  \Illuminate\Support\Collection  $certifications
     * @return array
     */
    private function generateRecommendations($trainer, $testimonials, $certifications): array
    {
        $recommendations = [];
        
        // Rating-based recommendations
        $avgRating = $testimonials->avg('rate') ?? 0;
        if ($avgRating < 3.5) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Consider reviewing training methods to improve client satisfaction'
            ];
        }
        
        // Certification recommendations
        $expiredCerts = $certifications->filter(function($cert) {
            return $cert->expiry_date && now()->gt($cert->expiry_date);
        });
        
        if ($expiredCerts->count() > 0) {
            $recommendations[] = [
                'type' => 'certification',
                'priority' => 'medium',
                'message' => 'Renew expired certifications to maintain credibility'
            ];
        }
        
        // Experience-based recommendations
        if ($trainer->experience < 2) {
            $recommendations[] = [
                'type' => 'development',
                'priority' => 'low',
                'message' => 'Consider pursuing additional certifications to enhance expertise'
            ];
        }
        
        return $recommendations;
    }
}