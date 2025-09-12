<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'duration' => $this->duration,
            'formatted_duration' => $this->formatted_duration,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'thumbnail' => $this->thumbnail,
            'total_videos' => $this->total_videos,
            'total_duration_seconds' => $this->total_duration_seconds,
            'videos' => WorkoutVideoResource::collection($this->whenLoaded('videos')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}