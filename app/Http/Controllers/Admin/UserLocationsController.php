<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * User Locations Controller
 * 
 * Handles CRUD operations for user location management in admin panel
 * Manages location information for all user types (admin, trainer, trainee)
 * 
 * @package     Go Globe CMS
 * @subpackage  Controllers\Admin
 * @category    User Management
 * @author      System Administrator
 * @since       1.0.0
 */
class UserLocationsController extends Controller
{
    /**
     * Display a listing of user locations.
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        try {
            $query = UserLocation::with('user');
            
            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('country', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('zipcode', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }
            
            // Filter by user role
            if ($request->filled('role')) {
                $query->whereHas('user', function ($userQuery) use ($request) {
                    $userQuery->where('role', $request->get('role'));
                });
            }
            
            // Filter by country
            if ($request->filled('country')) {
                $query->where('country', $request->get('country'));
            }
            
            // Filter by state
            if ($request->filled('state')) {
                $query->where('state', $request->get('state'));
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            $locations = $query->paginate(15)->withQueryString();
            
            // Get filter options
            $countries = UserLocation::distinct()->pluck('country')->filter()->sort();
            $states = UserLocation::distinct()->pluck('state')->filter()->sort();
            
            return view('admin.user-locations.index', compact(
                'locations', 
                'countries', 
                'states'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error fetching user locations: ' . $e->getMessage());
            return back()->with('error', 'Failed to load user locations. Please try again.');
        }
    }

    /**
     * Show the form for creating a new user location.
     * 
     * @return View
     */
    public function create(): View
    {
        try {
            // Get users who don't have a location yet
            $users = User::whereDoesntHave('location')
                        ->orderBy('name')
                        ->get(['id', 'name', 'email', 'role']);
            
            return view('admin.user-locations.create', compact('users'));
            
        } catch (\Exception $e) {
            Log::error('Error loading create location form: ' . $e->getMessage());
            return back()->with('error', 'Failed to load create form. Please try again.');
        }
    }

    /**
     * Store a newly created user location in storage.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id|unique:user_locations,user_id',
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
                'zipcode' => 'nullable|string|max:20',
            ]);

            DB::beginTransaction();
            
            $location = UserLocation::create($validated);
            
            DB::commit();
            
            Log::info('User location created successfully', [
                'location_id' => $location->id,
                'user_id' => $location->user_id,
                'admin_id' => auth()->id()
            ]);
            
            return redirect()
                ->route('admin.user-locations.show', $location->id)
                ->with('success', 'User location created successfully.');
                
        } catch (ValidationException $e) {
            DB::rollBack();
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please correct the validation errors.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user location: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Failed to create user location. Please try again.');
        }
    }

    /**
     * Display the specified user location.
     * 
     * @param UserLocation $userLocation
     * @return View
     */
    public function show(UserLocation $userLocation): View
    {
        try {
            $userLocation->load('user');
            
            return view('admin.user-locations.show', compact('userLocation'));
            
        } catch (\Exception $e) {
            Log::error('Error showing user location: ' . $e->getMessage());
            return back()->with('error', 'Failed to load user location details.');
        }
    }

    /**
     * Show the form for editing the specified user location.
     * 
     * @param UserLocation $userLocation
     * @return View
     */
    public function edit(UserLocation $userLocation): View
    {
        try {
            $userLocation->load('user');
            
            return view('admin.user-locations.edit', compact('userLocation'));
            
        } catch (\Exception $e) {
            Log::error('Error loading edit location form: ' . $e->getMessage());
            return back()->with('error', 'Failed to load edit form. Please try again.');
        }
    }

    /**
     * Update the specified user location in storage.
     * 
     * @param Request $request
     * @param UserLocation $userLocation
     * @return RedirectResponse
     */
    public function update(Request $request, UserLocation $userLocation): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
                'zipcode' => 'nullable|string|max:20',
            ]);

            DB::beginTransaction();
            
            $userLocation->update($validated);
            
            DB::commit();
            
            Log::info('User location updated successfully', [
                'location_id' => $userLocation->id,
                'user_id' => $userLocation->user_id,
                'admin_id' => auth()->id()
            ]);
            
            return redirect()
                ->route('admin.user-locations.show', $userLocation->id)
                ->with('success', 'User location updated successfully.');
                
        } catch (ValidationException $e) {
            DB::rollBack();
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please correct the validation errors.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating user location: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Failed to update user location. Please try again.');
        }
    }

    /**
     * Remove the specified user location from storage.
     * 
     * @param UserLocation $userLocation
     * @return RedirectResponse
     */
    public function destroy(UserLocation $userLocation): RedirectResponse
    {
        try {
            DB::beginTransaction();
            
            $userId = $userLocation->user_id;
            $userName = $userLocation->user->name ?? 'Unknown User';
            
            $userLocation->delete();
            
            DB::commit();
            
            Log::info('User location deleted successfully', [
                'location_id' => $userLocation->id,
                'user_id' => $userId,
                'admin_id' => auth()->id()
            ]);
            
            return redirect()
                ->route('admin.user-locations.index')
                ->with('success', "Location for {$userName} deleted successfully.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user location: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete user location. Please try again.');
        }
    }

    /**
     * Bulk delete user locations.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'location_ids' => 'required|array|min:1',
                'location_ids.*' => 'exists:user_locations,id'
            ]);

            DB::beginTransaction();
            
            $deletedCount = UserLocation::whereIn('id', $validated['location_ids'])->delete();
            
            DB::commit();
            
            Log::info('Bulk delete user locations completed', [
                'deleted_count' => $deletedCount,
                'admin_id' => auth()->id()
            ]);
            
            return redirect()
                ->route('admin.user-locations.index')
                ->with('success', "{$deletedCount} user locations deleted successfully.");
                
        } catch (ValidationException $e) {
            DB::rollBack();
            return back()
                ->withErrors($e->errors())
                ->with('error', 'Please select valid locations to delete.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk delete user locations: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete selected locations. Please try again.');
        }
    }

    /**
     * Get locations by user for AJAX requests.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationsByUser(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            
            if (!$userId) {
                return response()->json(['error' => 'User ID is required'], 400);
            }
            
            $location = UserLocation::where('user_id', $userId)->first();
            
            return response()->json([
                'success' => true,
                'location' => $location
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching user location: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch location'], 500);
        }
    }
}