<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificationRequest;
use App\Http\Requests\StoreTestimonialRequest;
use App\Http\Requests\UpdateTrainerProfileRequest;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\Testimonial;
use App\Models\TestimonialLikesDislike;
use App\Models\Availability;
use App\Models\BlockedTime;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * TrainerController
 * 
 * Handles trainer profile management, certifications, and testimonials
 */
class TrainerController extends Controller
{
    /**
     * Display a listing of trainers with advanced filtering options.
     * 
     * Supports filtering by:
     * - search: Search in trainer name, email, designation, about, and training_philosophy
     * - specializations: Filter by specialization IDs (comma-separated or array)
     * - locations: Filter by country, state, or city (comma-separated or array)
     * - price: Sort by workout prices (lowest_first, highest_first)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate filter parameters
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'specializations' => 'nullable|string',
                'locations' => 'nullable|string',
                'price_sort' => 'nullable|string|in:lowest_first,highest_first',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            // Initialize query builder
            $query = User::where('users.role', 'trainer')
                ->with([
                    'certifications', 
                    'receivedTestimonials.client',
                    'specializations:id,name',
                    'location:id,user_id,country,state,city',
                    'workouts:id,user_id,name,price',
                    'availabilities' => function ($query) {
                        $query->select('id', 'trainer_id', 'day_of_week', 'morning_available', 'evening_available', 
                                     'morning_start_time', 'morning_end_time', 'evening_start_time', 'evening_end_time');
                    }
                ])
                ->select('users.id', 'users.name', 'users.email', 'users.designation', 'users.profile_image', 'users.phone', 'users.experience', 'users.about', 'users.training_philosophy', 'users.created_at');

            // Apply search filter
            if (!empty($validated['search'])) {
                $searchTerm = trim($validated['search']);
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('users.name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('users.email', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('users.designation', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('users.about', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('users.training_philosophy', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Apply specializations filter
            if (!empty($validated['specializations'])) {
                $specializationIds = $this->parseFilterValues($validated['specializations']);
                
                if (!empty($specializationIds)) {
                    // Convert string IDs to integers for proper validation
                    $specializationIds = array_map('intval', array_filter($specializationIds, 'is_numeric'));
                    
                    if (!empty($specializationIds)) {
                        // Validate specialization IDs exist and are active
                        $validSpecializations = \App\Models\Specialization::whereIn('id', $specializationIds)
                            ->where('status', 'active')
                            ->pluck('id')
                            ->toArray();
                        
                        if (!empty($validSpecializations)) {
                            $query->whereHas('specializations', function ($q) use ($validSpecializations) {
                                $q->whereIn('specialization_id', $validSpecializations);
                            });
                        }
                    }
                }
            }

            // Apply location filter
            if (!empty($validated['locations'])) {
                $locationValues = $this->parseFilterValues($validated['locations']);
                
                if (!empty($locationValues)) {
                    $query->whereHas('location', function ($q) use ($locationValues) {
                        $q->where(function ($subQ) use ($locationValues) {
                            foreach ($locationValues as $location) {
                                $location = trim($location);
                                $subQ->orWhere('country', 'LIKE', "%{$location}%")
                                     ->orWhere('state', 'LIKE', "%{$location}%")
                                     ->orWhere('city', 'LIKE', "%{$location}%");
                            }
                        });
                    });
                }
            }

            // Apply price sorting based on trainer's workout prices
            if (!empty($validated['price_sort'])) {
                $priceOrder = $validated['price_sort'];
                
                // Add subquery to get minimum workout price for each trainer
                $query->leftJoin('workouts', 'users.id', '=', 'workouts.user_id')
                      ->selectRaw('MIN(workouts.price) as min_workout_price')
                      ->groupBy('users.id', 'users.name', 'users.email', 'users.designation', 
                               'users.profile_image', 'users.phone', 'users.experience', 
                               'users.about', 'users.training_philosophy', 'users.created_at');
                
                if ($priceOrder === 'lowest_first') {
                    $query->orderByRaw('min_workout_price IS NULL, min_workout_price ASC');
                } elseif ($priceOrder === 'highest_first') {
                    $query->orderByRaw('min_workout_price IS NULL, min_workout_price DESC');
                }
            } else {
                // Default sorting by creation date (newest first)
                $query->orderBy('users.created_at', 'desc');
            }

            // Set pagination parameters
            $perPage = $validated['per_page'] ?? 10;
            $perPage = min($perPage, 100); // Ensure maximum limit

            // Execute query with pagination
            $trainers = $query->paginate($perPage);

            // Transform the data to include additional computed fields
            $trainers->getCollection()->transform(function ($trainer) {
                // Add computed fields
                $trainer->specialization_names = $trainer->specializations->pluck('name')->implode(', ');
                $trainer->location_display = $this->formatLocationDisplay($trainer->location);
                $trainer->workout_count = $trainer->workouts->count();
                $trainer->min_workout_price = $trainer->workouts->min('price') ?? 0;
                $trainer->max_workout_price = $trainer->workouts->max('price') ?? 0;
                $trainer->avg_workout_price = $trainer->workouts->avg('price') ?? 0;
                $trainer->testimonial_count = $trainer->receivedTestimonials->count();
                
                // Format prices
                $trainer->formatted_min_price = $trainer->min_workout_price == 0 ? 'Free' : '$' . number_format($trainer->min_workout_price, 2);
                $trainer->formatted_max_price = $trainer->max_workout_price == 0 ? 'Free' : '$' . number_format($trainer->max_workout_price, 2);
                $trainer->formatted_avg_price = $trainer->avg_workout_price == 0 ? 'Free' : '$' . number_format($trainer->avg_workout_price, 2);
                
                // Add availability summary
                $trainer->availability_summary = $this->formatAvailabilitySummary($trainer->availabilities);
                $trainer->is_available_today = $this->isAvailableToday($trainer->availabilities);
                $trainer->next_available_slot = $this->getNextAvailableSlot($trainer->availabilities);
                
                return $trainer;
            });

            return response()->json([
                'success' => true,
                'message' => 'Trainers retrieved successfully',
                'data' => $trainers,
                'filters_applied' => [
                    'search' => $validated['search'] ?? null,
                    'specializations' => $validated['specializations'] ?? null,
                    'locations' => $validated['locations'] ?? null,
                    'price_sort' => $validated['price_sort'] ?? null
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid filter parameters provided',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log the error for debugging
            // \Log::error('TrainerController@index failed: ' . $e->getMessage(), [
            //     'request_data' => $request->all(),
            //     'user_id' => auth()->id(),
            //     'timestamp' => now(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trainers. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Parse filter values from comma-separated string or array format.
     * 
     * @param string $filterValues
     * @return array
     */
    private function parseFilterValues(string $filterValues): array
    {
        if (empty($filterValues)) {
            return [];
        }

        // Handle comma-separated values
        $values = explode(',', $filterValues);
        
        // Clean and filter values
        $cleanValues = array_filter(array_map('trim', $values), function ($value) {
            return !empty($value);
        });

        return array_values($cleanValues);
    }

    /**
     * Format location display string from UserLocation model.
     * 
     * @param \App\Models\UserLocation|null $location
     * @return string
     */
    private function formatLocationDisplay($location): string
    {
        if (!$location) {
            return 'Location not specified';
        }

        $parts = array_filter([
            $location->city,
            $location->state,
            $location->country
        ]);

        return !empty($parts) ? implode(', ', $parts) : 'Location not specified';
    }

    /**
     * Display the specified trainer profile with certifications and testimonials.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $trainer = User::where('id', $id)
                ->where('role', 'trainer')
                ->with([
                    'certifications',
                    'receivedTestimonials' => function ($query) {
                        $query->with('client:id,name')
                              ->orderBy('created_at', 'desc');
                    },
                    'availabilities' => function ($query) {
                        $query->select('id', 'trainer_id', 'day_of_week', 'morning_available', 'evening_available', 
                                     'morning_start_time', 'morning_end_time', 'evening_start_time', 'evening_end_time')
                              ->orderBy('day_of_week');
                    }
                ])
                ->select('id', 'name', 'email', 'phone', 'profile_image', 'designation', 'experience', 'about', 'training_philosophy', 'created_at')
                ->first();
            
            if (!$trainer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainer not found'
                ], 404);
            }
            
            // Add availability summary to trainer data
            $trainer->availability_summary = $this->formatAvailabilitySummary($trainer->availabilities);
            $trainer->is_available_today = $this->isAvailableToday($trainer->availabilities);
            $trainer->next_available_slot = $this->getNextAvailableSlot($trainer->availabilities);
            
            return response()->json([
                'success' => true,
                'message' => 'Trainer profile retrieved successfully',
                'data' => $trainer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trainer profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified trainer profile.
     * 
     * @param UpdateTrainerProfileRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateTrainerProfileRequest $request, string $id): JsonResponse
    {
        try {
            $trainer = User::where('id', $id)
                ->where('role', 'trainer')
                ->first();
            
            if (!$trainer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainer not found'
                ], 404);
            }
            
            $trainer->update($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Trainer profile updated successfully',
                'data' => $trainer->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update trainer profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a certification to the trainer's profile.
     * 
     * @param StoreCertificationRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function addCertification(StoreCertificationRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            $data['user_id'] = $id;
            
            // Handle file upload if present
            if ($request->hasFile('doc')) {
                $file = $request->file('doc');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('certifications', $filename, 'public');
                $data['doc'] = $path;
            }
            
            $certification = UserCertification::create($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Certification added successfully',
                'data' => $certification
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all certifications for the authenticated trainer.
     * 
     * @return JsonResponse
     */
    public function getCertifications(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only trainers can access certifications.'
                ], 403);
            }
            
            $certifications = $user->certifications()->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Certifications retrieved successfully',
                'data' => $certifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new certification for the authenticated trainer.
     * 
     * @param StoreCertificationRequest $request
     * @return JsonResponse
     */
    public function storeCertification(StoreCertificationRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only trainers can add certifications.'
                ], 403);
            }
            
            DB::beginTransaction();
            
            $data = $request->validated();
            $data['user_id'] = $user->id;
            
            // Handle file upload if present
            if ($request->hasFile('doc')) {
                $file = $request->file('doc');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('certifications', $filename, 'public');
                $data['doc'] = $path;
            }
            
            $certification = UserCertification::create($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Certification added successfully',
                'data' => $certification
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific certification.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function showCertification(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $certification = UserCertification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$certification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certification not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Certification retrieved successfully',
                'data' => $certification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a specific certification.
     * 
     * @param StoreCertificationRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateCertification(StoreCertificationRequest $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $certification = UserCertification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$certification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certification not found'
                ], 404);
            }
            
            DB::beginTransaction();
            
            $data = $request->validated();
            
            // Handle file upload if present
            if ($request->hasFile('doc')) {
                // Delete old file if exists
                if ($certification->doc && Storage::disk('public')->exists($certification->doc)) {
                    Storage::disk('public')->delete($certification->doc);
                }
                
                $file = $request->file('doc');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('certifications', $filename, 'public');
                $data['doc'] = $path;
            }
            
            $certification->update($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Certification updated successfully',
                'data' => $certification->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific certification.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function destroyCertification(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $certification = UserCertification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$certification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certification not found'
                ], 404);
            }
            
            DB::beginTransaction();
            
            // Delete associated file if exists
            if ($certification->doc && Storage::disk('public')->exists($certification->doc)) {
                Storage::disk('public')->delete($certification->doc);
            }
            
            $certification->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Certification deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a testimonial for the trainer.
     * 
     * @param StoreTestimonialRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function addTestimonial(StoreTestimonialRequest $request, string $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['trainer_id'] = $id;
            $data['client_id'] = Auth::id();
            
            $testimonial = Testimonial::create($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Testimonial added successfully',
                'data' => $testimonial->load('client:id,name')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add testimonial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Like a testimonial.
     * 
     * @param Request $request
     * @param string $testimonialId
     * @return JsonResponse
     */
    public function likeTestimonial(Request $request, string $testimonialId): JsonResponse
    {
        try {
            $user = Auth::user();
            $testimonial = Testimonial::findOrFail($testimonialId);
            
            DB::beginTransaction();
            
            // Find or create reaction record
            $reaction = TestimonialLikesDislike::firstOrCreate(
                [
                    'testimonial_id' => $testimonialId,
                    'user_id' => $user->id
                ],
                [
                    'like' => false,
                    'dislike' => false
                ]
            );
            
            $previousLike = $reaction->like;
            $previousDislike = $reaction->dislike;
            
            // Toggle like
            $reaction->setLike();
            
            // Update testimonial counters
            if (!$previousLike) {
                $testimonial->incrementLikes();
            }
            
            if ($previousDislike) {
                $testimonial->decrementDislikes();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Testimonial liked successfully',
                'data' => [
                    'likes' => $testimonial->fresh()->likes,
                    'dislikes' => $testimonial->fresh()->dislikes
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to like testimonial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dislike a testimonial.
     * 
     * @param Request $request
     * @param string $testimonialId
     * @return JsonResponse
     */
    public function dislikeTestimonial(Request $request, string $testimonialId): JsonResponse
    {
        try {
            $user = Auth::user();
            $testimonial = Testimonial::findOrFail($testimonialId);
            
            DB::beginTransaction();
            
            // Find or create reaction record
            $reaction = TestimonialLikesDislike::firstOrCreate(
                [
                    'testimonial_id' => $testimonialId,
                    'user_id' => $user->id
                ],
                [
                    'like' => false,
                    'dislike' => false
                ]
            );
            
            $previousLike = $reaction->like;
            $previousDislike = $reaction->dislike;
            
            // Toggle dislike
            $reaction->setDislike();
            
            // Update testimonial counters
            if (!$previousDislike) {
                $testimonial->incrementDislikes();
            }
            
            if ($previousLike) {
                $testimonial->decrementLikes();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Testimonial disliked successfully',
                'data' => [
                    'likes' => $testimonial->fresh()->likes,
                    'dislikes' => $testimonial->fresh()->dislikes
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to dislike testimonial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trainer availability for specific dates with time slots.
     * 
     * @param Request $request
     * @param string $id Trainer ID
     * @return JsonResponse
     */
    public function getAvailability(Request $request, string $id): JsonResponse
    {
        try {
            // Validate trainer exists and is a trainer
            $trainer = User::where('id', $id)
                ->where('role', 'trainer')
                ->first();

            if (!$trainer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainer not found'
                ], 404);
            }

            // Get date parameters or default to today and next 7 days
            $dateFrom = $request->get('date_from', now()->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->addDays(7)->format('Y-m-d'));

            // Validate date format
            try {
                $startDate = Carbon::createFromFormat('Y-m-d', $dateFrom);
                $endDate = Carbon::createFromFormat('Y-m-d', $dateTo);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Use Y-m-d format.'
                ], 400);
            }

            // Get trainer's weekly availability settings
            $weeklyAvailability = Availability::forTrainer($id)
                ->orderBy('day_of_week')
                ->get()
                ->keyBy('day_of_week');

            // Get blocked times for the date range
            $blockedTimes = BlockedTime::forTrainer($id)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->get()
                ->groupBy('date');

            // Get existing bookings for the date range
            $existingBookings = Schedule::forTrainer($id)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->where('status', '!=', Schedule::STATUS_CANCELLED)
                ->get()
                ->groupBy('date');

            $availabilityData = [];
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateString = $currentDate->format('Y-m-d');
                $dayOfWeek = $currentDate->dayOfWeek;
                
                // Get weekly availability for this day
                $dayAvailability = $weeklyAvailability->get($dayOfWeek);
                
                $dayData = [
                    'date' => $dateString,
                    'day_name' => $currentDate->format('l'),
                    'available' => false,
                    'time_slots' => []
                ];

                if ($dayAvailability) {
                    $timeSlots = [];
                    
                    // Generate morning slots if available
                    if ($dayAvailability->isMorningAvailable()) {
                        $morningSlots = $this->generateTimeSlots(
                            $dayAvailability->morning_start_time,
                            $dayAvailability->morning_end_time,
                            60 // 60 minutes per slot
                        );
                        $timeSlots = array_merge($timeSlots, $morningSlots);
                    }
                    
                    // Generate evening slots if available
                    if ($dayAvailability->isEveningAvailable()) {
                        $eveningSlots = $this->generateTimeSlots(
                            $dayAvailability->evening_start_time,
                            $dayAvailability->evening_end_time,
                            60 // 60 minutes per slot
                        );
                        $timeSlots = array_merge($timeSlots, $eveningSlots);
                    }
                    
                    // Filter out blocked times and existing bookings
                    $dayBlockedTimes = $blockedTimes->get($dateString, collect());
                    $dayBookings = $existingBookings->get($dateString, collect());
                    
                    foreach ($timeSlots as &$slot) {
                        $slot['available'] = true;
                        $slot['reason'] = null;
                        
                        // Check against blocked times
                        foreach ($dayBlockedTimes as $blockedTime) {
                            if ($this->isTimeSlotBlocked($slot, $blockedTime)) {
                                $slot['available'] = false;
                                $slot['reason'] = $blockedTime->reason ?: 'Blocked';
                                break;
                            }
                        }
                        
                        // Check against existing bookings
                        if ($slot['available']) {
                            foreach ($dayBookings as $booking) {
                                if ($this->isTimeSlotBooked($slot, $booking)) {
                                    $slot['available'] = false;
                                    $slot['reason'] = 'Booked';
                                    break;
                                }
                            }
                        }
                    }
                    
                    $dayData['time_slots'] = $timeSlots;
                    $dayData['available'] = collect($timeSlots)->contains('available', true);
                }

                $availabilityData[] = $dayData;
                $currentDate->addDay();
            }

            return response()->json([
                'success' => true,
                'message' => 'Trainer availability retrieved successfully',
                'data' => [
                    'trainer' => [
                        'id' => $trainer->id,
                        'name' => $trainer->name,
                        'profile_image' => $trainer->profile_image ? asset('storage/' . $trainer->profile_image) : null
                    ],
                    'date_range' => [
                        'from' => $dateFrom,
                        'to' => $dateTo
                    ],
                    'availability' => $availabilityData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trainer availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate time slots for a given time range.
     * 
     * @param string $startTime
     * @param string $endTime
     * @param int $slotDuration Duration in minutes
     * @return array
     */
    private function generateTimeSlots(string $startTime, string $endTime, int $slotDuration = 60): array
    {
        $slots = [];
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);
        
        while ($start->lt($end)) {
            $slotEnd = $start->copy()->addMinutes($slotDuration);
            
            if ($slotEnd->lte($end)) {
                $slots[] = [
                    'start_time' => $start->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'duration_minutes' => $slotDuration
                ];
            }
            
            $start->addMinutes($slotDuration);
        }
        
        return $slots;
    }

    /**
     * Check if a time slot is blocked by a blocked time.
     * 
     * @param array $slot
     * @param BlockedTime $blockedTime
     * @return bool
     */
    private function isTimeSlotBlocked(array $slot, BlockedTime $blockedTime): bool
    {
        $slotStart = Carbon::createFromFormat('H:i', $slot['start_time']);
        $slotEnd = Carbon::createFromFormat('H:i', $slot['end_time']);
        $blockedStart = Carbon::createFromFormat('H:i', $blockedTime->start_time);
        $blockedEnd = Carbon::createFromFormat('H:i', $blockedTime->end_time);
        
        // Check if slot overlaps with blocked time
        return $slotStart->lt($blockedEnd) && $slotEnd->gt($blockedStart);
    }

    /**
     * Check if a time slot is booked by an existing booking.
     * 
     * @param array $slot
     * @param Schedule $booking
     * @return bool
     */
    private function isTimeSlotBooked(array $slot, Schedule $booking): bool
    {
        $slotStart = Carbon::createFromFormat('H:i', $slot['start_time']);
        $slotEnd = Carbon::createFromFormat('H:i', $slot['end_time']);
        $bookingStart = Carbon::createFromFormat('H:i', $booking->start_time);
        $bookingEnd = Carbon::createFromFormat('H:i', $booking->end_time);
        
        // Check if slot overlaps with booking
        return $slotStart->lt($bookingEnd) && $slotEnd->gt($bookingStart);
    }

    /**
     * Format availability data for API responses.
     * 
     * @param \Illuminate\Database\Eloquent\Collection $availability
     * @return array
     */
    private function formatAvailabilitySummary($availability): array
    {
        $summary = [];
        $dayNames = [
            0 => 'Sunday',
            1 => 'Monday', 
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        ];

        foreach ($availability as $slot) {
            $dayName = $dayNames[$slot->day_of_week] ?? 'Unknown';
            
            // Add morning availability
            if ($slot->morning_available && $slot->morning_start_time && $slot->morning_end_time) {
                // Convert datetime objects to Carbon instances for formatting
                $morningStart = Carbon::parse($slot->morning_start_time);
                $morningEnd = Carbon::parse($slot->morning_end_time);
                
                $summary[] = [
                    'day' => $dayName,
                    'day_number' => $slot->day_of_week,
                    'period' => 'morning',
                    'start_time' => $morningStart->format('H:i:s'),
                    'end_time' => $morningEnd->format('H:i:s'),
                    'formatted_time' => $morningStart->format('g:i A') . ' - ' . $morningEnd->format('g:i A')
                ];
            }
            
            // Add evening availability
            if ($slot->evening_available && $slot->evening_start_time && $slot->evening_end_time) {
                // Convert datetime objects to Carbon instances for formatting
                $eveningStart = Carbon::parse($slot->evening_start_time);
                $eveningEnd = Carbon::parse($slot->evening_end_time);
                
                $summary[] = [
                    'day' => $dayName,
                    'day_number' => $slot->day_of_week,
                    'period' => 'evening',
                    'start_time' => $eveningStart->format('H:i:s'),
                    'end_time' => $eveningEnd->format('H:i:s'),
                    'formatted_time' => $eveningStart->format('g:i A') . ' - ' . $eveningEnd->format('g:i A')
                ];
            }
        }

        return $summary;
    }

    /**
     * Check if trainer is available today.
     * 
     * @param \Illuminate\Database\Eloquent\Collection $availability
     * @return bool
     */
    private function isAvailableToday($availability): bool
    {
        $today = Carbon::now()->dayOfWeek;
        
        return $availability->where('day_of_week', $today)
                           ->where(function($slot) {
                               return $slot->morning_available || $slot->evening_available;
                           })
                           ->isNotEmpty();
    }

    /**
     * Get the next available time slot for the trainer.
     * 
     * @param \Illuminate\Database\Eloquent\Collection $availability
     * @return array|null
     */
    private function getNextAvailableSlot($availability): ?array
    {
        $now = Carbon::now();
        $currentDay = $now->dayOfWeek;
        $currentTime = $now->format('H:i:s');
        
        $dayNames = [
            0 => 'Sunday',
            1 => 'Monday', 
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        ];

        // First, check if there's availability later today
        $todaySlot = $availability->where('day_of_week', $currentDay)->first();
        
        if ($todaySlot) {
            // Check evening availability if current time is before evening start
            if ($todaySlot->evening_available && 
                $todaySlot->evening_start_time) {
                
                $eveningStart = Carbon::parse($todaySlot->evening_start_time);
                
                if ($eveningStart->format('H:i:s') > $currentTime) {
                    $eveningEnd = Carbon::parse($todaySlot->evening_end_time);
                    
                    return [
                        'day' => $dayNames[$todaySlot->day_of_week],
                        'day_number' => $todaySlot->day_of_week,
                        'date' => $now->format('Y-m-d'),
                        'period' => 'evening',
                        'start_time' => $eveningStart->format('H:i:s'),
                        'end_time' => $eveningEnd->format('H:i:s'),
                        'formatted_time' => $eveningStart->format('g:i A') . ' - ' . $eveningEnd->format('g:i A')
                    ];
                }
            }
        }

        // Look for next available day within the next 7 days
        for ($i = 1; $i <= 7; $i++) {
            $checkDay = ($currentDay + $i) % 7;
            $checkDate = $now->copy()->addDays($i);
            
            $daySlot = $availability->where('day_of_week', $checkDay)->first();
            
            if ($daySlot) {
                // Check morning availability first
                if ($daySlot->morning_available && $daySlot->morning_start_time) {
                    $morningStart = Carbon::parse($daySlot->morning_start_time);
                    $morningEnd = Carbon::parse($daySlot->morning_end_time);
                    
                    return [
                        'day' => $dayNames[$daySlot->day_of_week],
                        'day_number' => $daySlot->day_of_week,
                        'date' => $checkDate->format('Y-m-d'),
                        'period' => 'morning',
                        'start_time' => $morningStart->format('H:i:s'),
                        'end_time' => $morningEnd->format('H:i:s'),
                        'formatted_time' => $morningStart->format('g:i A') . ' - ' . $morningEnd->format('g:i A')
                    ];
                }
                
                // Check evening availability if no morning availability
                if ($daySlot->evening_available && $daySlot->evening_start_time) {
                    $eveningStart = Carbon::parse($daySlot->evening_start_time);
                    $eveningEnd = Carbon::parse($daySlot->evening_end_time);
                    
                    return [
                        'day' => $dayNames[$daySlot->day_of_week],
                        'day_number' => $daySlot->day_of_week,
                        'date' => $checkDate->format('Y-m-d'),
                        'period' => 'evening',
                        'start_time' => $eveningStart->format('H:i:s'),
                        'end_time' => $eveningEnd->format('H:i:s'),
                        'formatted_time' => $eveningStart->format('g:i A') . ' - ' . $eveningEnd->format('g:i A')
                    ];
                }
            }
        }

        return null; // No availability found in the next 7 days
    }

    /**
     * Add a new client by trainer
     * 
     * Creates a new client account with the provided information
     * Only trainers can add clients to the system
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function addClient(Request $request): JsonResponse
    {
        try {
            // Validate trainer authentication
            $trainer = Auth::user();
            if (!$trainer || !$trainer->isTrainerRole()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only trainers can add clients.'
                ], 403);
            }

            // Validate request data based on the form fields from the image
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'phone' => 'required|string|max:20',
                'fitness_goals' => 'nullable|string|max:1000',
                'current_fitness_level' => 'nullable|string|in:Beginner,Intermediate,Advanced',
                'health_considerations' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create client user account
            $clientData = [
                'name' => trim($request->first_name . ' ' . $request->last_name),
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => 'client',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'), // Default password
                'email_verified_at' => now(), // Auto-verify trainer created clients
            ];

            $client = User::create($clientData);

            // Create fitness goals if provided
            if ($request->filled('fitness_goals')) {
                $client->goals()->create([
                    'name' => $request->fitness_goals,
                    'status' => 1
                ]);
            }

            // Log client creation
            \Illuminate\Support\Facades\Log::info('New client added by trainer', [
                'trainer_id' => $trainer->id,
                'trainer_name' => $trainer->name,
                'client_id' => $client->id,
                'client_name' => $client->name,
                'client_email' => $client->email
            ]);

            // Prepare response data
            $responseData = [
                'id' => $client->id,
                'name' => $client->name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'fitness_goals' => $request->fitness_goals,
                'current_fitness_level' => $request->current_fitness_level,
                'health_considerations' => $request->health_considerations,
                'role' => $client->role,
                'status' => 'active',
                'created_at' => $client->created_at->toISOString(),
                'member_since' => $client->created_at->format('M Y')
            ];

            return response()->json([
                'success' => true,
                'message' => 'Client added successfully',
                'data' => $responseData
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to add client via trainer API: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add client. Please try again.'
            ], 500);
        }
    }

    /**
     * Get all clients with search and filtering capabilities
     * 
     * Retrieves clients with optional search functionality
     * Includes client goals, progress, and basic information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClients(Request $request): JsonResponse
    {
        try {
            // Validate trainer authentication
            $trainer = Auth::user();
            if (!$trainer || !$trainer->isTrainerRole()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only trainers can view clients.'
                ], 403);
            }

            // Validate search parameters
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'fitness_level' => 'nullable|string|in:Beginner,Intermediate,Advanced',
                'sort_by' => 'nullable|string|in:name,email,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'per_page' => 'nullable|integer|min:5|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Build query for clients
            $query = User::where('role', 'client')
                ->with([
                    'goals:id,user_id,name,status',
                    'clientSchedules' => function($q) use ($trainer) {
                        $q->where('trainer_id', $trainer->id)
                          ->select('id', 'client_id', 'trainer_id', 'date', 'status');
                    }
                ])
                ->select('id', 'name', 'email', 'phone', 'profile_image', 'created_at');

            // Apply search filter
            if ($request->filled('search')) {
                $searchTerm = trim($request->search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = $request->get('per_page', 20);
            $clients = $query->paginate($perPage);

            // Transform client data for response
            $transformedClients = $clients->getCollection()->map(function ($client) {
                // Get next session with this trainer
                $nextSession = $client->clientSchedules
                    ->where('date', '>=', now()->format('Y-m-d'))
                    ->where('status', '!=', 'cancelled')
                    ->sortBy('date')
                    ->first();

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'profile_image' => $client->profile_image ? asset('storage/' . $client->profile_image) : null,
                    'fitness_goals' => $client->goals->where('status', 1)->pluck('name')->implode(', '),
                    'goals_count' => $client->goals->where('status', 1)->count(),
                    'next_session' => $nextSession ? [
                        'date' => $nextSession->date,
                        'status' => $nextSession->status
                    ] : null,
                    'total_sessions' => $client->clientSchedules->count(),
                    'member_since' => $client->created_at->format('M Y'),
                    'created_at' => $client->created_at->toISOString()
                ];
            });

            // Prepare pagination data
            $paginationData = [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'from' => $clients->firstItem(),
                'to' => $clients->lastItem()
            ];

            \Illuminate\Support\Facades\Log::info('Clients retrieved by trainer', [
                'trainer_id' => $trainer->id,
                'search_params' => $request->only(['search', 'fitness_level', 'sort_by', 'sort_order']),
                'results_count' => $clients->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Clients retrieved successfully',
                'data' => $transformedClients,
                'pagination' => $paginationData
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to retrieve clients via trainer API: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clients. Please try again.'
            ], 500);
        }
    }

}
