<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificationRequest;
use App\Http\Requests\StoreTestimonialRequest;
use App\Http\Requests\UpdateTrainerProfileRequest;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * TrainerWebController
 * 
 * Handles web-based trainer profile management, certifications, and testimonials
 */
class TrainerWebController extends Controller
{
    /**
     * Display a listing of trainers.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            $trainers = User::where('role', 'trainer')
                ->with(['certifications', 'receivedTestimonials.client'])
                ->withCount(['certifications', 'receivedTestimonials'])
                ->paginate(12);
            
            return view('trainers.index', compact('trainers'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load trainers: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified trainer profile.
     * 
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
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
                ->firstOrFail();
            
            return view('trainers.show', compact('trainer'));
        } catch (\Exception $e) {
            return redirect()->route('trainers.index')->with('error', 'Trainer not found.');
        }
    }

    /**
     * Show the form for editing the trainer profile.
     * 
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        try {
            $trainer = User::where('id', $id)
                ->where('role', 'trainer')
                ->firstOrFail();
            
            // Check if user can edit this profile
            if (Auth::id() !== $trainer->id && Auth::user()->role !== 'admin') {
                return redirect()->route('trainers.show', $id)->with('error', 'You can only edit your own profile.');
            }
            
            return view('trainers.edit', compact('trainer'));
        } catch (\Exception $e) {
            return redirect()->route('trainers.index')->with('error', 'Trainer not found.');
        }
    }

    /**
     * Update the specified trainer profile.
     * 
     * @param UpdateTrainerProfileRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateTrainerProfileRequest $request, $id)
    {
        try {
            $trainer = User::where('id', $id)
                ->where('role', 'trainer')
                ->firstOrFail();
            
            // Handle profile image upload
            $data = $request->validated();
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($trainer->profile_image) {
                    Storage::disk('public')->delete($trainer->profile_image);
                }
                
                $file = $request->file('profile_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('profile_images', $filename, 'public');
                $data['profile_image'] = $path;
            }
            
            $trainer->update($data);
            
            return redirect()->route('trainers.show', $trainer->id)
                ->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update profile: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display a listing of trainer certifications.
     * 
     * @param int $trainerId
     * @return \Illuminate\View\View
     */
    public function indexCertifications($trainerId)
    {
        try {
            $trainer = User::where('id', $trainerId)
                ->where('role', 'trainer')
                ->with(['certifications' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }])
                ->firstOrFail();
            
            // Check if user can view certifications
            if (Auth::id() !== $trainer->id && Auth::user()->role !== 'admin') {
                return redirect()->route('trainers.show', $trainerId)->with('error', 'You can only view your own certifications.');
            }
            
            $certifications = $trainer->certifications ?? [];
            
            return view('trainer.certifications.index', compact('trainer', 'certifications'));
        } catch (\Exception $e) {
            return redirect()->route('trainers.index')->with('error', 'Trainer not found.');
        }
    }

    /**
     * Show the form for creating a new certification.
     * 
     * @param int $trainerId
     * @return \Illuminate\View\View
     */
    public function createCertification($trainerId)
    {
        try {
            $trainer = User::where('id', $trainerId)
                ->where('role', 'trainer')
                ->with('certifications')
                ->firstOrFail();
            
            // Check if user can add certification
            if (Auth::id() !== $trainer->id && Auth::user()->role !== 'admin') {
                return redirect()->route('trainers.show', $trainerId)->with('error', 'You can only add certifications to your own profile.');
            }
            
            return view('trainers.certifications.create', compact('trainer'));
        } catch (\Exception $e) {
            return redirect()->route('trainers.index')->with('error', 'Trainer not found.');
        }
    }

    /**
     * Store a new certification.
     * 
     * @param StoreCertificationRequest $request
     * @param int $trainerId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeCertification(StoreCertificationRequest $request, $trainerId)
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            $data['user_id'] = $trainerId;
            
            // Handle file upload if present
            if ($request->hasFile('doc')) {
                $file = $request->file('doc');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('certifications', $filename, 'public');
                $data['doc'] = $path;
            }
            
            UserCertification::create($data);
            
            DB::commit();
            
            return redirect()->route('trainers.show', $trainerId)
                ->with('success', 'Certification added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to add certification: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for creating a new testimonial.
     * 
     * @param int $trainerId
     * @return \Illuminate\View\View
     */
    public function createTestimonial($trainerId)
    {
        try {
            $trainer = User::where('id', $trainerId)
                ->where('role', 'trainer')
                ->with('receivedTestimonials')
                ->firstOrFail();
            
            // Check if user is a client
            if (Auth::user()->role !== 'client') {
                return redirect()->route('trainers.show', $trainerId)->with('error', 'Only clients can write testimonials.');
            }
            
            return view('trainers.testimonials.create', compact('trainer'));
        } catch (\Exception $e) {
            return redirect()->route('trainers.index')->with('error', 'Trainer not found.');
        }
    }

    /**
     * Store a new testimonial.
     * 
     * @param StoreTestimonialRequest $request
     * @param int $trainerId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeTestimonial(StoreTestimonialRequest $request, $trainerId)
    {
        try {
            $data = $request->validated();
            $data['trainer_id'] = $trainerId;
            $data['client_id'] = Auth::id();
            
            Testimonial::create($data);
            
            return redirect()->route('trainers.show', $trainerId)
                ->with('success', 'Thank you for your review! It has been submitted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to submit testimonial: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete a certification.
     * 
     * @param int $id
     * @param int $certificationId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteCertification($id, $certificationId)
    {
        try {
            $trainer = User::where('id', $id)
                ->where('role', 'trainer')
                ->firstOrFail();
            
            // Check if user can delete this certification
            if (Auth::id() !== $trainer->id && Auth::user()->role !== 'admin') {
                return redirect()->route('trainers.show', $id)->with('error', 'You can only delete your own certifications.');
            }
            
            $certification = UserCertification::where('id', $certificationId)
                ->where('user_id', $trainer->id)
                ->firstOrFail();
            
            // Delete the document file if it exists
            if ($certification->doc) {
                Storage::disk('public')->delete($certification->doc);
            }
            
            $certification->delete();
            
            return redirect()->back()->with('success', 'Certification deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete certification: ' . $e->getMessage());
        }
    }

    /**
     * Delete trainer profile image.
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteImage($id)
    {
        try {
            $trainer = User::where('id', $id)
                ->where('role', 'trainer')
                ->firstOrFail();
            
            // Check if user can delete this image
            if (Auth::id() !== $trainer->id && Auth::user()->role !== 'admin') {
                return redirect()->route('trainers.show', $id)->with('error', 'You can only delete your own profile image.');
            }
            
            if ($trainer->profile_image) {
                Storage::disk('public')->delete($trainer->profile_image);
                $trainer->update(['profile_image' => null]);
                
                return redirect()->back()->with('success', 'Profile image deleted successfully!');
            }
            
            return redirect()->back()->with('error', 'No profile image to delete.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete image: ' . $e->getMessage());
        }
    }
}
