<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WorkoutVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'title',
        'description',
        'video_url',
        'video_type',
        'thumbnail',
        'duration',
        'order',
        'is_preview',
        'metadata'
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = [
        'formatted_duration',
        'thumbnail_url',
        'embed_url'
    ];

    // Relationships
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    // Accessors
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) return 'N/A';
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) return null;

        if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
            return $this->thumbnail;
        }

        return Storage::url($this->thumbnail);
    }

    public function getEmbedUrlAttribute(): string
    {
        return match($this->video_type) {
            'youtube' => $this->getYouTubeEmbedUrl(),
            'vimeo' => $this->getVimeoEmbedUrl(),
            default => $this->video_url
        };
    }

    // Methods
    private function getYouTubeEmbedUrl(): string
    {
        // Extract video ID from various YouTube URL formats
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $this->video_url, $matches);
        
        if (isset($matches[1])) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        return $this->video_url;
    }

    private function getVimeoEmbedUrl(): string
    {
        // Extract video ID from Vimeo URL
        preg_match('/vimeo\.com\/(\d+)/', $this->video_url, $matches);
        
        if (isset($matches[1])) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        return $this->video_url;
    }

    public function isYouTube(): bool
    {
        return $this->video_type === 'youtube' || 
               str_contains($this->video_url, 'youtube.com') || 
               str_contains($this->video_url, 'youtu.be');
    }

    public function isVimeo(): bool
    {
        return $this->video_type === 'vimeo' || 
               str_contains($this->video_url, 'vimeo.com');
    }

    public function isLocalFile(): bool
    {
        return $this->video_type === 'file';
    }
}