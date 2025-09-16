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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * TrainerController
 * 
 * Handles trainer profile management, certifications, and testimonials
 */
class TrainerController extends Controller
{
    /**
     * Display a listing of trainers.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $trainers = User::where('role', 'trainer')
                ->with(['certifications', 'receivedTestimonials.client'])
                ->select('id', 'name', 'email', 'designation', 'experience', 'about', 'training_philosophy', 'created_at')
                ->paginate(10);
            
            return response()->json([
                'success' => true,
                'message' => 'Trainers retrieved successfully',
                'data' => $trainers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trainers',
                'error' => $e->getMessage()
            ], 500);
        }
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
                    }
                ])
                ->select('id', 'name', 'email', 'designation', 'experience', 'about', 'training_philosophy', 'created_at')
                ->first();
            
            if (!$trainer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainer not found'
                ], 404);
            }
            
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
}
