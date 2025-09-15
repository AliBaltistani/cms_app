<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'duration',
        'description',
        'is_active',
        'thumbnail',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    protected $appends = [
        'formatted_duration',
        'total_videos',
        'total_duration_seconds'
    ];

    // Relationships
    public function videos(): HasMany
    {
        return $this->hasMany(WorkoutVideo::class)->orderBy('order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }



    public function scopeWithVideos(Builder $query): Builder
    {
        return $query->with(['videos' => function ($query) {
            $query->orderBy('order');
        }]);
    }

    // Accessors
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . ' minutes';
    }

    public function getTotalVideosAttribute(): int
    {
        return $this->videos()->count();
    }

    public function getTotalDurationSecondsAttribute(): int
    {
        return $this->videos()->sum('duration') ?? 0;
    }

    // Methods
    public function addVideo(array $videoData): WorkoutVideo
    {
        if (!isset($videoData['order'])) {
            $videoData['order'] = $this->videos()->max('order') + 1;
        }

        return $this->videos()->create($videoData);
    }

    public function reorderVideos(array $videoIds): void
    {
        foreach ($videoIds as $index => $videoId) {
            $this->videos()->where('id', $videoId)->update(['order' => $index + 1]);
        }
    }

    
}