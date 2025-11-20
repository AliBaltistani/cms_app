<?php

namespace App\Http\Controllers\Admin;

use App\Models\Program;
use App\Models\ProgramVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

/**
 * Program Video Controller
 * 
 * Manages CRUD operations for program videos
 * Handles video uploads, editing, and ordering
 */
class ProgramVideoController extends Controller
{
    /**
     * Display a listing of program videos.
     */
    public function index($programId)
    {
        $program = Program::findOrFail($programId);
        $videos = $program->videos()->orderBy('order')->get();
        
        return view('admin.programs.videos.index', [
            'program' => $program,
            'videos' => $videos
        ]);
    }

    /**
     * Show the form for creating a new video.
     */
    public function create($programId)
    {
        $program = Program::findOrFail($programId);
        $nextOrder = $program->videos()->max('order') + 1;
        
        return view('admin.programs.videos.create', [
            'program' => $program,
            'nextOrder' => $nextOrder
        ]);
    }

    /**
     * Store a newly created video in storage.
     */
    public function store(Request $request, $programId)
    {
        $program = Program::findOrFail($programId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_type' => 'required|in:youtube,vimeo,url,file',
            'video_url' => 'required_if:video_type,youtube,vimeo,url|nullable|url',
            'video_file' => 'required_if:video_type,file|nullable|file|mimes:mp4,avi,mov,wmv,flv,webm,mkv|max:102400',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'duration' => 'nullable|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'is_preview' => 'nullable|boolean',
        ]);

        // Handle file upload
        if ($request->hasFile('video_file')) {
            $file = $request->file('video_file');
            $videoPath = $file->store('program-videos', 'public');
            $validated['video_url'] = $videoPath;
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $thumbnailPath = $thumbnail->store('program-thumbnails', 'public');
            $validated['thumbnail'] = $thumbnailPath;
        }

        // Auto-order if not provided
        if (!isset($validated['order'])) {
            $validated['order'] = $program->videos()->max('order') + 1;
        }

        $validated['program_id'] = $program->id;

        $video = ProgramVideo::create($validated);

        return redirect()->route('program-videos.index', $program->id)
            ->with('success', 'Video added successfully!');
    }

    /**
     * Show the form for editing the specified video.
     */
    public function edit($programId, $videoId)
    {
        $program = Program::findOrFail($programId);
        $video = $program->videos()->findOrFail($videoId);
        
        return view('admin.programs.videos.edit', [
            'program' => $program,
            'video' => $video
        ]);
    }

    /**
     * Update the specified video in storage.
     */
    public function update(Request $request, $programId, $videoId)
    {
        $program = Program::findOrFail($programId);
        $video = $program->videos()->findOrFail($videoId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_type' => 'required|in:youtube,vimeo,url,file',
            'video_url' => 'required_if:video_type,youtube,vimeo,url|nullable|url',
            'video_file' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,webm,mkv|max:102400',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'duration' => 'nullable|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'is_preview' => 'nullable|boolean',
        ]);

        // Handle file upload
        if ($request->hasFile('video_file')) {
            // Delete old file if exists
            if ($video->video_file && Storage::disk('public')->exists($video->video_file)) {
                Storage::disk('public')->delete($video->video_file);
            }
            
            $file = $request->file('video_file');
            $videoPath = $file->store('program-videos', 'public');
            $validated['video_url'] = $videoPath;
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if exists
            if ($video->thumbnail && Storage::disk('public')->exists($video->thumbnail)) {
                Storage::disk('public')->delete($video->thumbnail);
            }
            
            $thumbnail = $request->file('thumbnail');
            $thumbnailPath = $thumbnail->store('program-thumbnails', 'public');
            $validated['thumbnail'] = $thumbnailPath;
        }

        $video->update($validated);

        return redirect()->route('program-videos.index', $program->id)
            ->with('success', 'Video updated successfully!');
    }

    /**
     * Remove the specified video from storage.
     */
    public function destroy($programId, $videoId)
    {
        $program = Program::findOrFail($programId);
        $video = $program->videos()->findOrFail($videoId);

        // Delete video file
        if ($video->video_file && Storage::disk('public')->exists($video->video_file)) {
            Storage::disk('public')->delete($video->video_file);
        }

        // Delete thumbnail
        if ($video->thumbnail && Storage::disk('public')->exists($video->thumbnail)) {
            Storage::disk('public')->delete($video->thumbnail);
        }

        $video->delete();

        return redirect()->route('program-videos.index', $program->id)
            ->with('success', 'Video deleted successfully!');
    }

    /**
     * Show reorder form
     */
    public function reorderForm($programId)
    {
        $program = Program::findOrFail($programId);
        $videos = $program->videos()->orderBy('order')->get();

        return view('admin.programs.videos.reorder', [
            'program' => $program,
            'videos' => $videos
        ]);
    }

    /**
     * Update video order
     */
    public function updateOrder(Request $request, $programId)
    {
        $program = Program::findOrFail($programId);
        $videoIds = $request->input('video_ids', []);

        foreach ($videoIds as $index => $videoId) {
            ProgramVideo::where('id', $videoId)
                ->where('program_id', $program->id)
                ->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Videos reordered successfully!']);
    }
}
