<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\Testimonial;
use App\Models\TrainerSubscription;
use App\Models\WorkoutAssignment;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Client API Controller
 * 
 * Handles client-specific operations for finding trainers and viewing trainer profiles
 * Provides comprehensive trainer information including certifications and testimonials
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\API\Client
 * @category    Client Management API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ClientController extends ApiBaseController
{
    /**
     * Find trainers with filtering and search capabilities
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findTrainers(Request $request): JsonResponse
    {
        try {
            // Validate search parameters
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'specialization' => 'nullable|string|max:100',
                'location' => 'nullable|string|max:100',
                'experience_min' => 'nullable|integer|min:0|max:50',
                'experience_max' => 'nullable|integer|min:0|max:50',
                'rating_min' => 'nullable|numeric|min:1|max:5',
                'sort_by' => 'nullable|in:name,experience,rating,created_at',
                'sort_order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:5|max:50'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Build query for trainers
            $query = User::where('role', 'trainer')
                ->with([
                    'certifications:id,user_id,certificate_name,created_at',
                    'receivedTestimonials:id,trainer_id,client_id,name,rate,comments,likes,dislikes,created_at'
                ]);
            
            // Apply search filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('designation', 'LIKE', "%{$search}%")
                      ->orWhere('about', 'LIKE', "%{$search}%")
                      ->orWhere('training_philosophy', 'LIKE', "%{$search}%");
                });
            }
            
            // Filter by specialization (designation)
            if ($request->filled('specialization')) {
                $query->where('designation', 'LIKE', "%{$request->specialization}%");
            }
            
            // Filter by experience range
            if ($request->filled('experience_min')) {
                $query->where('experience', '>=', $request->experience_min);
            }
            
            if ($request->filled('experience_max')) {
                $query->where('experience', '<=', $request->experience_max);
            }
            
            // Add calculated fields for rating and testimonial count
            $query->withCount('receivedTestimonials as total_testimonials')
                  ->withAvg('receivedTestimonials as average_rating', 'rate');
            
            // Apply rating filter
            if ($request->filled('rating_min')) {
                $query->having('average_rating', '>=', $request->rating_min);
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            switch ($sortBy) {
                case 'name':
                    $query->orderBy('name', $sortOrder);
                    break;
                case 'experience':
                    $query->orderBy('experience', $sortOrder);
                    break;
                case 'rating':
                    $query->orderBy('average_rating', $sortOrder);
                    break;
                default:
                    $query->orderBy('created_at', $sortOrder);
                    break;
            }
            
            // Paginate results
            $perPage = $request->get('per_page', 10);
            $trainers = $query->paginate($perPage);
            
            // Transform trainer data for API response
            $trainers->getCollection()->transform(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                    'phone' => $trainer->phone,
                    'designation' => $trainer->designation,
                    'experience' => $trainer->experience,
                    'about' => $trainer->about,
                    'training_philosophy' => $trainer->training_philosophy,
                    'profile_image' => $trainer->profile_image ? asset('storage/' . $trainer->profile_image) : null,
                    'total_testimonials' => $trainer->total_testimonials ?? 0,
                    'average_rating' => $trainer->average_rating ? round($trainer->average_rating, 1) : 0,
                    'certifications_count' => $trainer->certifications->count(),
                    'recent_certifications' => $trainer->certifications->take(3)->map(function ($cert) {
                        return [
                            'id' => $cert->id,
                            'name' => $cert->certificate_name,
                            'date_added' => $cert->created_at->format('M Y')
                        ];
                    }),
                    'member_since' => $trainer->created_at->format('M Y'),
                    'created_at' => $trainer->created_at->toISOString()
                ];
            });
            
            Log::info('Trainers search performed', [
                'client_id' => Auth::id(),
                'search_params' => $request->only(['search', 'specialization', 'location', 'experience_min', 'experience_max', 'rating_min']),
                'results_count' => $trainers->count()
            ]);
            
            return $this->sendResponse($trainers, 'Trainers retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainers: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Search Failed', ['error' => 'Unable to search trainers'], 500);
        }
    }
    
    /**
     * Get detailed trainer profile with all information
     * 
     * @param  int  $trainerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrainerProfile(int $trainerId): JsonResponse
    {
        try {
            // Find trainer with all related data
            $trainer = User::where('id', $trainerId)
                ->where('role', 'trainer')
                ->with([
                    'certifications' => function ($query) {
                        $query->orderBy('created_at', 'desc');
                    },
                    'receivedTestimonials' => function ($query) {
                        $query->orderBy('created_at', 'desc')
                              ->with('client:id,name,profile_image');
                    }
                ])
                ->first();
            
            if (!$trainer) {
                return $this->sendError('Trainer Not Found', ['error' => 'Trainer not found'], 404);
            }
            
            // Calculate trainer statistics
            $totalTestimonials = $trainer->receivedTestimonials->count();
            $averageRating = $totalTestimonials > 0 ? $trainer->receivedTestimonials->avg('rate') : 0;
            $totalLikes = $trainer->receivedTestimonials->sum('likes');
            $totalDislikes = $trainer->receivedTestimonials->sum('dislikes');
            
            // Prepare comprehensive trainer profile data
            $trainerProfile = [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'email' => $trainer->email,
                'phone' => $trainer->phone,
                'designation' => $trainer->designation,
                'experience' => $trainer->experience,
                'about' => $trainer->about,
                'training_philosophy' => $trainer->training_philosophy,
                'profile_image' => $trainer->profile_image ? asset('storage/' . $trainer->profile_image) : null,
                'member_since' => $trainer->created_at->format('F Y'),
                'created_at' => $trainer->created_at->toISOString(),
                'has_subscribed' => Auth::check() && Auth::user()->isClientRole()
                    ? Auth::user()->hasActiveSubscriptionTo($trainer->id)
                    : false,
                
                // Statistics
                'statistics' => [
                    'total_testimonials' => $totalTestimonials,
                    'average_rating' => round($averageRating, 1),
                    'total_likes' => $totalLikes,
                    'total_dislikes' => $totalDislikes,
                    'total_certifications' => $trainer->certifications->count(),
                    'years_experience' => $trainer->experience ?? 0
                ],
                
                // Certifications
                'certifications' => $trainer->certifications->map(function ($certification) {
                    return [
                        'id' => $certification->id,
                        'certificate_name' => $certification->certificate_name,
                        'document_url' => $certification->doc ? asset('storage/' . $certification->doc) : null,
                        'has_document' => !empty($certification->doc),
                        'date_added' => $certification->created_at->format('d M, Y'),
                        'created_at' => $certification->created_at->toISOString()
                    ];
                }),
                
                // Client Testimonials
                'testimonials' => $trainer->receivedTestimonials->map(function ($testimonial) {
                    return [
                        'id' => $testimonial->id,
                        'client_name' => $testimonial->name,
                        'client_profile_image' => $testimonial->client && $testimonial->client->profile_image 
                            ? asset('storage/' . $testimonial->client->profile_image) 
                            : null,
                        'rating' => $testimonial->rate,
                        'comments' => $testimonial->comments,
                        'likes' => $testimonial->likes,
                        'dislikes' => $testimonial->dislikes,
                        'date_posted' => $testimonial->created_at->format('d M, Y'),
                        'created_at' => $testimonial->created_at->toISOString()
                    ];
                })
            ];
            
            Log::info('Trainer profile viewed', [
                'client_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'trainer_name' => $trainer->name
            ]);
            
            return $this->sendResponse($trainerProfile, 'Trainer profile retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainer profile: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Profile Retrieval Failed', ['error' => 'Unable to retrieve trainer profile'], 500);
        }
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $client = Auth::user();
        if (!$client || !$client->isClientRole()) {
            return $this->sendError('Unauthorized', ['error' => 'Client access required'], 401);
        }

        $trainer = User::where('id', $request->trainer_id)->where('role', 'trainer')->first();
        if (!$trainer) {
            return $this->sendError('Trainer Not Found', ['error' => 'Trainer not found'], 404);
        }

        $subscription = TrainerSubscription::where('client_id', $client->id)
            ->where('trainer_id', $trainer->id)
            ->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'active',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        } else {
            TrainerSubscription::create([
                'client_id' => $client->id,
                'trainer_id' => $trainer->id,
                'status' => 'active',
                'subscribed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscribed successfully'
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $client = Auth::user();
        if (!$client || !$client->isClientRole()) {
            return $this->sendError('Unauthorized', ['error' => 'Client access required'], 401);
        }

        $subscription = TrainerSubscription::where('client_id', $client->id)
            ->where('trainer_id', $request->trainer_id)
            ->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'inactive',
                'unsubscribed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unsubscribed successfully'
        ]);
    }

    public function subscriptions(): JsonResponse
    {
        $client = Auth::user();
        if (!$client || !$client->isClientRole()) {
            return $this->sendError('Unauthorized', ['error' => 'Client access required'], 401);
        }

        $subs = TrainerSubscription::where('client_id', $client->id)
            ->with(['trainer:id,name'])
            ->orderBy('subscribed_at', 'desc')
            ->get()
            ->map(function ($sub) {
                return [
                    'trainer_id' => $sub->trainer_id,
                    'trainer_name' => optional($sub->trainer)->name,
                    'status' => $sub->status,
                    'subscribed_at' => optional($sub->subscribed_at)->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subs
        ]);
    }

    public function manageSubscription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|integer|exists:users,id',
            'action' => 'nullable|in:subscribe,unsubscribe',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $client = Auth::user();
        if (!$client || !$client->isClientRole()) {
            return $this->sendError('Unauthorized', ['error' => 'Client access required'], 401);
        }

        $desiredStatus = null;
        if ($request->filled('action')) {
            $desiredStatus = $request->action === 'subscribe' ? 'active' : 'inactive';
        } elseif ($request->filled('status')) {
            $desiredStatus = $request->status;
        } else {
            return $this->sendError('Validation Error', ['error' => 'Provide action or status'], 422);
        }

        $trainer = User::where('id', $request->trainer_id)->where('role', 'trainer')->first();
        if (!$trainer) {
            return $this->sendError('Trainer Not Found', ['error' => 'Trainer not found'], 404);
        }

        $subscription = TrainerSubscription::where('client_id', $client->id)
            ->where('trainer_id', $trainer->id)
            ->first();

        if ($desiredStatus === 'active') {
            if ($subscription) {
                $subscription->update([
                    'status' => 'active',
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);
            } else {
                TrainerSubscription::create([
                    'client_id' => $client->id,
                    'trainer_id' => $trainer->id,
                    'status' => 'active',
                    'subscribed_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Subscribed successfully'
            ]);
        }

        if ($subscription) {
            $subscription->update([
                'status' => 'inactive',
                'unsubscribed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unsubscribed successfully'
        ]);
    }

    public function getClientProfile(Request $request): JsonResponse
    {
        try {
            $client = Auth::user();
            if (!$client || !$client->isClientRole()) {
                return $this->sendError('Unauthorized', ['error' => 'Client access required'], 401);
            }

            $activeGoal = $client->goals()->where('status', 1)->orderByDesc('created_at')->first();

            $basicDetails = [
                'id' => $client->id,
                'name' => $client->name,
                'profile_image' => $client->profile_image ? asset('storage/' . $client->profile_image) : null,
                'goal' => $activeGoal ? $activeGoal->name : null,
            ];

            $metrics = [
                'weight' => $client->weight !== null ? round($client->weight * 2.20462, 2) : null,
                'body_fat' => $client->body_fat ?? null,
                'unit' => 'lbs'
            ];

            $clientId = $client->id;
            $today = Carbon::today();

            $assignmentBase = WorkoutAssignment::forClients()
                ->where('assigned_to', $clientId)
                ->with(['workout:id,name,duration']);

            $sessionBase = Schedule::forClient($clientId)
                ->with(['trainer:id,name']);

            $upcomingAssignments = (clone $assignmentBase)
                ->whereDate('due_date', '>=', $today->toDateString())
                ->whereIn('status', ['assigned', 'in_progress'])
                ->orderBy('due_date', 'asc')
                ->limit(10)
                ->get()
                ->map(function ($assignment) {
                    return [
                        'type' => 'workout',
                        'name' => optional($assignment->workout)->name,
                        'date' => optional($assignment->due_date)?->format('Y-m-d'),
                        'day' => optional($assignment->due_date)?->format('l'),
                        'time' => null,
                        'sort_at' => optional($assignment->due_date)?->format('Y-m-d 00:00'),
                    ];
                });

            $upcomingSessions = (clone $sessionBase)
                ->whereDate('date', '>=', $today->toDateString())
                ->withStatus(Schedule::STATUS_CONFIRMED)
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->limit(10)
                ->get()
                ->map(function ($session) {
                    return [
                        'type' => 'session',
                        'name' => 'Training Session',
                        'date' => $session->date->format('Y-m-d'),
                        'day' => $session->date->format('l'),
                        'time' => $session->start_time->format('H:i') . ' - ' . $session->end_time->format('H:i'),
                        'sort_at' => $session->date->format('Y-m-d') . ' ' . $session->start_time->format('H:i'),
                    ];
                });

            $recentAssignments = (clone $assignmentBase)
                ->whereDate('due_date', '<=', $today->toDateString())
                ->orderBy('due_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($assignment) {
                    return [
                        'type' => 'workout',
                        'name' => optional($assignment->workout)->name,
                        'date' => optional($assignment->due_date)?->format('Y-m-d'),
                        'day' => optional($assignment->due_date)?->format('l'),
                        'time' => null,
                        'sort_at' => optional($assignment->due_date)?->format('Y-m-d 00:00'),
                    ];
                });

            $recentSessions = (clone $sessionBase)
                ->whereDate('date', '<=', $today->toDateString())
                ->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($session) {
                    return [
                        'type' => 'session',
                        'name' => 'Training Session',
                        'date' => $session->date->format('Y-m-d'),
                        'day' => $session->date->format('l'),
                        'time' => $session->start_time->format('H:i') . ' - ' . $session->end_time->format('H:i'),
                        'sort_at' => $session->date->format('Y-m-d') . ' ' . $session->start_time->format('H:i'),
                    ];
                });

            $upcoming = $upcomingAssignments->merge($upcomingSessions)->sortBy('sort_at')->values();
            $recent = $recentAssignments->merge($recentSessions)->sortByDesc('sort_at')->values();

            $workouts = $upcoming->take(10)->values();

            $monthlyProgress = [];
            for ($i = 11; $i >= 0; $i--) {
                $start = Carbon::now()->startOfMonth()->subMonths($i);
                $end = Carbon::now()->startOfMonth()->subMonths($i)->endOfMonth();

                $assignedCount = WorkoutAssignment::forClients()
                    ->where('assigned_to', $clientId)
                    ->whereBetween('assigned_at', [$start, $end])
                    ->count();

                $completedCount = WorkoutAssignment::forClients()
                    ->where('assigned_to', $clientId)
                    ->whereNotNull('completed_at')
                    ->whereBetween('completed_at', [$start, $end])
                    ->count();

                $value = $assignedCount > 0 ? round(($completedCount / $assignedCount) * 100, 2) : 0;
                $monthlyProgress[] = [
                    'month' => $start->format('M Y'),
                    'value' => $value,
                ];
            }

            $response = [
                'basic_details' => $basicDetails,
                'metrics' => $metrics,
                'workouts' => $workouts,
                'upcoming_workouts' => $upcoming,
                'recent_workouts' => $recent,
                'progress_monthly' => $monthlyProgress,
            ];

            Log::info('Client profile retrieved', [
                'client_id' => $client->id,
                'items_upcoming' => $upcoming->count(),
                'items_recent' => $recent->count(),
            ]);

            return $this->sendResponse($response, 'Client profile retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve client profile: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('Profile Retrieval Failed', ['error' => 'Unable to retrieve client profile'], 500);
        }
    }
    
    /**
     * Get trainer certifications only
     * 
     * @param  int  $trainerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrainerCertifications(int $trainerId): JsonResponse
    {
        try {
            // Verify trainer exists
            $trainer = User::where('id', $trainerId)
                ->where('role', 'trainer')
                ->first();
            
            if (!$trainer) {
                return $this->sendError('Trainer Not Found', ['error' => 'Trainer not found'], 404);
            }
            
            // Get trainer certifications
            $certifications = UserCertification::where('user_id', $trainerId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($certification) {
                    return [
                        'id' => $certification->id,
                        'certificate_name' => $certification->certificate_name,
                        'document_url' => $certification->doc ? asset('storage/' . $certification->doc) : null,
                        'has_document' => !empty($certification->doc),
                        'date_added' => $certification->created_at->format('d M, Y'),
                        'created_at' => $certification->created_at->toISOString()
                    ];
                });
            
            return $this->sendResponse([
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'designation' => $trainer->designation
                ],
                'certifications' => $certifications,
                'total_certifications' => $certifications->count()
            ], 'Trainer certifications retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainer certifications: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Certifications Retrieval Failed', ['error' => 'Unable to retrieve trainer certifications'], 500);
        }
    }
    
    /**
     * Get trainer testimonials only
     * 
     * @param  int  $trainerId
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrainerTestimonials(int $trainerId, Request $request): JsonResponse
    {
        try {
            // Validate pagination parameters
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:5|max:50',
                'sort_by' => 'nullable|in:rating,date,likes',
                'sort_order' => 'nullable|in:asc,desc'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Verify trainer exists
            $trainer = User::where('id', $trainerId)
                ->where('role', 'trainer')
                ->first();
            
            if (!$trainer) {
                return $this->sendError('Trainer Not Found', ['error' => 'Trainer not found or inactive'], 404);
            }
            
            // Build testimonials query
            $query = Testimonial::where('trainer_id', $trainerId)
                ->with('client:id,name,profile_image');
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'date');
            $sortOrder = $request->get('sort_order', 'desc');
            
            switch ($sortBy) {
                case 'rating':
                    $query->orderBy('rate', $sortOrder);
                    break;
                case 'likes':
                    $query->orderBy('likes', $sortOrder);
                    break;
                default:
                    $query->orderBy('created_at', $sortOrder);
                    break;
            }
            
            // Paginate testimonials
            $perPage = $request->get('per_page', 10);
            $testimonials = $query->paginate($perPage);
            
            // Transform testimonials data
            $testimonials->getCollection()->transform(function ($testimonial) {
                return [
                    'id' => $testimonial->id,
                    'client_name' => $testimonial->name,
                    'client_profile_image' => $testimonial->client && $testimonial->client->profile_image 
                        ? asset('storage/' . $testimonial->client->profile_image) 
                        : null,
                    'rating' => $testimonial->rate,
                    'comments' => $testimonial->comments,
                    'likes' => $testimonial->likes,
                    'dislikes' => $testimonial->dislikes,
                    'date_posted' => $testimonial->created_at->format('d M, Y'),
                    'created_at' => $testimonial->created_at->toISOString()
                ];
            });
            
            // Calculate testimonial statistics
            $totalTestimonials = Testimonial::where('trainer_id', $trainerId)->count();
            $averageRating = Testimonial::where('trainer_id', $trainerId)->avg('rate');
            $totalLikes = Testimonial::where('trainer_id', $trainerId)->sum('likes');
            $totalDislikes = Testimonial::where('trainer_id', $trainerId)->sum('dislikes');
            
            return $this->sendResponse([
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'designation' => $trainer->designation
                ],
                'testimonials' => $testimonials,
                'statistics' => [
                    'total_testimonials' => $totalTestimonials,
                    'average_rating' => round($averageRating, 1),
                    'total_likes' => $totalLikes,
                    'total_dislikes' => $totalDislikes
                ]
            ], 'Trainer testimonials retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainer testimonials: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trainer_id' => $trainerId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Testimonials Retrieval Failed', ['error' => 'Unable to retrieve trainer testimonials'], 500);
        }
    }
}