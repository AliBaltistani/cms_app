<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutVideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_id' => $this->workout_id,
            'title' => $this->title,
            'description' => $this->description,
            'video_url' => $this->video_url,
            'video_type' => $this->video_type,
            'embed_url' => $this->embed_url,
            'thumbnail' => $this->thumbnail,
            'thumbnail_url' => $this->thumbnail_url,
            'duration' => $this->duration,
            'formatted_duration' => $this->formatted_duration,
            'order' => $this->order,
            'is_preview' => $this->is_preview,
            'metadata' => $this->metadata,
            'is_youtube' => $this->isYouTube(),
            'is_vimeo' => $this->isVimeo(),
            'is_local_file' => $this->isLocalFile(),
            'workout' => new WorkoutResource($this->whenLoaded('workout')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}