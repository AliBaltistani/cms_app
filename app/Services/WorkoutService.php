<?php

namespace App\Services;

use App\Models\Workout;
use App\Models\WorkoutVideo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkoutService
{
    /**
     * Create a new workout
     */
    public function createWorkout(array $data): Workout
    {
        return DB::transaction(function () use ($data) {
            // Handle thumbnail upload
            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail'], 'workouts');
            }

            return Workout::create($data);
        });
    }

    /**
     * Update an existing workout
     */
    public function updateWorkout(Workout $workout, array $data): Workout
    {
        return DB::transaction(function () use ($workout, $data) {
            // Handle thumbnail upload
            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                // Delete old thumbnail
                if ($workout->thumbnail && Storage::disk('public')->exists($workout->thumbnail)) {
                    Storage::disk('public')->delete($workout->thumbnail);
                }
                
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail'], 'workouts');
            }

            $workout->update($data);
            return $workout->fresh();
        });
    }

    /**
     * Delete a workout and its related data
     */
    public function deleteWorkout(Workout $workout): bool
    {
        return DB::transaction(function () use ($workout) {
            // Delete workout thumbnail
            if ($workout->thumbnail && Storage::disk('public')->exists($workout->thumbnail)) {
                Storage::disk('public')->delete($workout->thumbnail);
            }

            // Delete video thumbnails
            foreach ($workout->videos as $video) {
                if ($video->thumbnail && Storage::disk('public')->exists($video->thumbnail)) {
                    Storage::disk('public')->delete($video->thumbnail);
                }
            }

            return $workout->delete();
        });
    }

    /**
     * Add a video to a workout
     */
    public function addVideoToWorkout(Workout $workout, array $data): WorkoutVideo
    {
        return DB::transaction(function () use ($workout, $data) {
            // Handle thumbnail upload
            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail'], 'workout-videos');
            }

            // Auto-detect video type if not provided
            if (!isset($data['video_type']) || empty($data['video_type'])) {
                $data['video_type'] = $this->detectVideoType($data['video_url']);
            }

            // Set order if not provided
            if (!isset($data['order'])) {
                $data['order'] = $workout->videos()->max('order') + 1;
            }

            return $workout->addVideo($data);
        });
    }

    /**
     * Update a workout video
     */
    public function updateWorkoutVideo(WorkoutVideo $video, array $data): WorkoutVideo
    {
        return DB::transaction(function () use ($video, $data) {
            // Handle thumbnail upload
            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                // Delete old thumbnail
                if ($video->thumbnail && Storage::disk('public')->exists($video->thumbnail)) {
                    Storage::disk('public')->delete($video->thumbnail);
                }
                
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail'], 'workout-videos');
            }

            // Auto-detect video type if URL changed
            if (isset($data['video_url']) && $data['video_url'] !== $video->video_url) {
                $data['video_type'] = $this->detectVideoType($data['video_url']);
            }

            $video->update($data);
            return $video->fresh();
        });
    }

    /**
     * Delete a workout video
     */
    public function deleteWorkoutVideo(WorkoutVideo $video): bool
    {
        return DB::transaction(function () use ($video) {
            // Delete thumbnail
            if ($video->thumbnail && Storage::disk('public')->exists($video->thumbnail)) {
                Storage::disk('public')->delete($video->thumbnail);
            }

            return $video->delete();
        });
    }

    /**
     * Duplicate a workout with all its videos
     */
    public function duplicateWorkout(Workout $originalWorkout): Workout
    {
        return DB::transaction(function () use ($originalWorkout) {
            // Create new workout
            $workoutData = $originalWorkout->toArray();
            unset($workoutData['id'], $workoutData['created_at'], $workoutData['updated_at']);
            
            // Add "Copy" to the name
            $workoutData['name'] = $workoutData['name'] . ' (Copy)';
            
            // Handle thumbnail duplication
            if ($originalWorkout->thumbnail) {
                $workoutData['thumbnail'] = $this->duplicateFile($originalWorkout->thumbnail, 'workouts');
            }

            $newWorkout = Workout::create($workoutData);

            // Duplicate videos
            foreach ($originalWorkout->videos as $originalVideo) {
                $videoData = $originalVideo->toArray();
                unset($videoData['id'], $videoData['workout_id'], $videoData['created_at'], $videoData['updated_at']);
                
                // Handle video thumbnail duplication
                if ($originalVideo->thumbnail) {
                    $videoData['thumbnail'] = $this->duplicateFile($originalVideo->thumbnail, 'workout-videos');
                }

                $newWorkout->addVideo($videoData);
            }

            return $newWorkout->load('videos');
        });
    }

    /**
     * Upload thumbnail image
     */
    private function uploadThumbnail(UploadedFile $file, string $folder): string
    {
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folder, $filename, 'public');
    }

    /**
     * Duplicate an existing file
     */
    private function duplicateFile(string $originalPath, string $folder): string
    {
        if (!Storage::disk('public')->exists($originalPath)) {
            return $originalPath;
        }

        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $filename = Str::random(40) . '.' . $extension;
        $newPath = $folder . '/' . $filename;

        Storage::disk('public')->copy($originalPath, $newPath);

        return $newPath;
    }

    /**
     * Auto-detect video type based on URL
     */
    private function detectVideoType(string $url): string
    {
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        if (str_contains($url, 'vimeo.com')) {
            return 'vimeo';
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return 'url';
        }

        return 'file';
    }

    /**
     * Get workout analytics data
     */
    public function getWorkoutAnalytics(Workout $workout): array
    {
        return [
            'total_videos' => $workout->videos()->count(),
            'total_duration_seconds' => $workout->videos()->sum('duration'),
            'average_video_duration' => $workout->videos()->avg('duration'),
            'preview_videos_count' => $workout->videos()->where('is_preview', true)->count(),
            'video_types' => $workout->videos()
                ->selectRaw('video_type, COUNT(*) as count')
                ->groupBy('video_type')
                ->pluck('count', 'video_type')
                ->toArray(),
        ];
    }

    /**
     * Search workouts with advanced filters
     */
    public function searchWorkouts(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Workout::query();

        // Basic filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }


        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }


        // Duration filters
        if (!empty($filters['min_duration'])) {
            $query->where('duration', '>=', $filters['min_duration']);
        }

        if (!empty($filters['max_duration'])) {
            $query->where('duration', '<=', $filters['max_duration']);
        }

        // Include videos if requested
        if (!empty($filters['include_videos'])) {
            $query->with('videos');
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Bulk operations for workouts
     */
    public function bulkUpdateWorkouts(array $workoutIds, array $data): int
    {
        return DB::transaction(function () use ($workoutIds, $data) {
            // Only allow certain fields for bulk update
            $allowedFields = ['is_active'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            return Workout::whereIn('id', $workoutIds)->update($updateData);
        });
    }

    /**
     * Bulk delete workouts
     */
    public function bulkDeleteWorkouts(array $workoutIds): int
    {
        return DB::transaction(function () use ($workoutIds) {
            $workouts = Workout::whereIn('id', $workoutIds)->get();
            
            foreach ($workouts as $workout) {
                $this->deleteWorkout($workout);
            }

            return $workouts->count();
        });
    }
}