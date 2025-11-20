<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProgramVideo Model
 * 
 * Represents videos linked to workout programs
 * Videos can be YouTube, Vimeo, direct URLs, or uploaded files
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    Program Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ProgramVideo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_id',
        'title',
        'description',
        'video_type',
        'video_url',
        'video_file',
        'thumbnail',
        'duration',
        'order',
        'is_preview',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_preview' => 'boolean',
        'duration' => 'integer',
        'order' => 'integer',
    ];

    /**
     * The attributes that should be appended to the model.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'formatted_duration',
        'embed_url'
    ];

    /**
     * Get the program that owns the video.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Scope a query to order videos by their order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Scope a query to only include preview videos.
     */
    public function scopePreview($query)
    {
        return $query->where('is_preview', true);
    }

    /**
     * Scope a query to filter by video type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('video_type', $type);
    }

    /**
     * Get formatted duration in HH:MM:SS format
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return 'Unknown';
        }
        
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get the full video URL based on type
     */
    public function getEmbedUrlAttribute(): string
    {
        switch ($this->video_type) {
            case 'youtube':
                $videoId = $this->extractYoutubeId($this->video_url);
                return "https://www.youtube.com/embed/{$videoId}";
            case 'vimeo':
                $videoId = $this->extractVimeoId($this->video_url);
                return "https://player.vimeo.com/video/{$videoId}";
            case 'file':
            case 'url':
                return $this->video_url;
            default:
                return '';
        }
    }

    /**
     * Extract YouTube video ID from URL
     */
    private function extractYoutubeId($url): string
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube\.googleapis\.com/v/)([^"&?\s]{11})%i', $url, $match);
        return $match[1] ?? '';
    }

    /**
     * Extract Vimeo video ID from URL
     */
    private function extractVimeoId($url): string
    {
        preg_match('/(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(\d+)/', $url, $match);
        return $match[1] ?? '';
    }
}
