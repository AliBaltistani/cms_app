<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Availability;
use App\Models\BlockedTime;
use App\Models\SessionCapacity;
use App\Models\BookingSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Admin Booking Controller
 * 
 * Handles admin web interface for booking management and overrides
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Admin
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class BookingController extends Controller
{
    /**
     * Display a listing of all bookings
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $query = Schedule::with(['trainer:id,name,email', 'client:id,name,email'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trainer_id')) {
            $query->where('trainer_id', $request->trainer_id);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $bookings = $query->paginate(20);

        // Get filter options
        $trainers = User::where('role', 'trainer')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $clients = User::where('role', 'client')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $statuses = Schedule::getStatuses();

        return view('admin.bookings.index', compact(
            'bookings',
            'trainers',
            'clients',
            'statuses'
        ));
    }

    /**
     * Show the form for creating a new booking
     * 
     * @return View
     */
    public function create(): View
    {
        $trainers = User::where('role', 'trainer')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $clients = User::where('role', 'client')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.bookings.create', compact('trainers', 'clients'));
    }

    /**
     * Store a newly created booking
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Verify trainer and client roles
            $trainer = User::where('id', $request->trainer_id)
                ->where('role', 'trainer')
                ->first();

            $client = User::where('id', $request->client_id)
                ->where('role', 'client')
                ->first();

            if (!$trainer || !$client) {
                return redirect()->back()
                    ->with('error', 'Invalid trainer or client selected')
                    ->withInput();
            }

            // Check for conflicts (unless admin override)
            if (!$request->has('override_conflicts')) {
                $conflictingBooking = Schedule::where('trainer_id', $request->trainer_id)
                    ->where('date', $request->date)
                    ->where('status', '!=', Schedule::STATUS_CANCELLED)
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                              ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                              ->orWhere(function ($q) use ($request) {
                                  $q->where('start_time', '<=', $request->start_time)
                                    ->where('end_time', '>=', $request->end_time);
                              });
                    })
                    ->exists();

                if ($conflictingBooking) {
                    return redirect()->back()
                        ->with('error', 'Time slot conflicts with existing booking')
                        ->withInput();
                }
            }

            $schedule = Schedule::create([
                'trainer_id' => $request->trainer_id,
                'client_id' => $request->client_id,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            // Create Google Calendar event if booking is confirmed
            $googleEventResult = null;
            if ($request->status === Schedule::STATUS_CONFIRMED) {
                $googleEventResult = $schedule->createGoogleCalendarEvent();
            }

            $message = 'Booking created successfully';
            if ($request->status === Schedule::STATUS_CONFIRMED) {
                if ($googleEventResult) {
                    $message .= ' with Google Calendar event and Meet link';
                } else {
                    $message .= ' (Google Calendar event could not be created)';
                }
            }

            return redirect()->route('admin.bookings.show', $schedule->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating booking: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified booking
     * 
     * @param int $id
     * @return View
     */
    public function show(int $id ): View
    {
        $booking = Schedule::with([
            'trainer:id,name,email,phone',
            'client:id,name,email,phone'
        ])->findOrFail($id);

        return view('admin.bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified booking
     * 
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $booking = Schedule::with([
            'trainer:id,name,email',
            'client:id,name,email'
        ])->findOrFail($id);

        $trainers = User::where('role', 'trainer')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $clients = User::where('role', 'client')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $statuses = Schedule::getStatuses();

        return view('admin.bookings.edit', compact(
            'booking',
            'trainers',
            'clients',
            'statuses'
        ));
    }

    /**
     * Update the specified booking
     * 
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $booking = Schedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Check for conflicts (unless admin override or same booking)
            if (!$request->has('override_conflicts')) {
                $conflictingBooking = Schedule::where('trainer_id', $request->trainer_id)
                    ->where('date', $request->date)
                    ->where('id', '!=', $id)
                    ->where('status', '!=', Schedule::STATUS_CANCELLED)
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                              ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                              ->orWhere(function ($q) use ($request) {
                                  $q->where('start_time', '<=', $request->start_time)
                                    ->where('end_time', '>=', $request->end_time);
                              });
                    })
                    ->exists();

                if ($conflictingBooking) {
                    return redirect()->back()
                        ->with('error', 'Time slot conflicts with existing booking')
                        ->withInput();
                }
            }

            $oldStatus = $booking->status;
            
            $booking->update([
                'trainer_id' => $request->trainer_id,
                'client_id' => $request->client_id,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            // Handle Google Calendar events based on status change
            $googleMessage = '';
            
            if ($request->status === Schedule::STATUS_CONFIRMED && $oldStatus !== Schedule::STATUS_CONFIRMED) {
                // Create or update Google Calendar event when confirming
                $googleEventResult = $booking->hasGoogleCalendarEvent() 
                    ? $booking->updateGoogleCalendarEvent() 
                    : $booking->createGoogleCalendarEvent();
                    
                if ($googleEventResult) {
                    $googleMessage = ' with Google Calendar event and Meet link';
                } else {
                    $googleMessage = ' (Google Calendar event could not be created)';
                }
            } elseif ($request->status === Schedule::STATUS_CANCELLED && $booking->hasGoogleCalendarEvent()) {
                // Delete Google Calendar event when cancelling
                $deleteResult = $booking->deleteGoogleCalendarEvent();
                if ($deleteResult) {
                    $googleMessage = ' and Google Calendar event deleted';
                } else {
                    $googleMessage = ' (Google Calendar event could not be deleted)';
                }
            } elseif ($oldStatus !== $request->status && $booking->hasGoogleCalendarEvent()) {
                // Update existing Google Calendar event for other status changes
                $updateResult = $booking->updateGoogleCalendarEvent();
                if ($updateResult) {
                    $googleMessage = ' and Google Calendar event updated';
                }
            }

            $message = 'Booking updated successfully' . $googleMessage;

            return redirect()->route('admin.bookings.show', $booking->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating booking: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified booking
     * 
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $booking = Schedule::findOrFail($id);
            $booking->delete();

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Booking deleted successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting booking: ' . $e->getMessage());
        }
    }

    /**
     * Get booking statistics for dashboard
     * 
     * @return View
     */
    public function dashboard(): View
    {
        $today = now()->toDateString();
        $thisWeek = [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()];
        $thisMonth = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];

        $stats = [
            'today_bookings' => Schedule::where('date', $today)->count(),
            'pending_bookings' => Schedule::where('status', Schedule::STATUS_PENDING)
                ->where('date', '>=', $today)
                ->count(),
            'confirmed_bookings' => Schedule::where('status', Schedule::STATUS_CONFIRMED)
                ->where('date', '>=', $today)
                ->count(),
            'week_bookings' => Schedule::whereBetween('date', $thisWeek)->count(),
            'month_bookings' => Schedule::whereBetween('date', $thisMonth)->count(),
            'total_trainers' => User::where('role', 'trainer')->count(),
            'total_clients' => User::where('role', 'client')->count(),
        ];

        // Recent bookings
        $recentBookings = Schedule::with(['trainer:id,name', 'client:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Upcoming bookings
        $upcomingBookings = Schedule::with(['trainer:id,name', 'client:id,name'])
            ->where('date', '>=', $today)
            ->where('status', '!=', Schedule::STATUS_CANCELLED)
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        return view('admin.bookings.dashboard', compact(
            'stats',
            'recentBookings',
            'upcomingBookings'
        ));
    }

    /**
     * Bulk update booking statuses
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'exists:schedules,id',
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            Schedule::whereIn('id', $request->booking_ids)
                ->update(['status' => $request->status]);

            $count = count($request->booking_ids);
            return redirect()->back()
                ->with('success', "Updated {$count} booking(s) successfully");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating bookings: ' . $e->getMessage());
        }
    }

    /**
     * Export bookings to CSV
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $query = Schedule::with(['trainer:id,name,email', 'client:id,name,email']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trainer_id')) {
            $query->where('trainer_id', $request->trainer_id);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $bookings = $query->orderBy('date')->orderBy('start_time')->get();

        $filename = 'bookings_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($handle, [
                'ID',
                'Trainer',
                'Client',
                'Date',
                'Start Time',
                'End Time',
                'Status',
                'Notes',
                'Created At'
            ]);

            // CSV data
            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->id,
                    $booking->trainer->name,
                    $booking->client->name,
                    $booking->date,
                    $booking->start_time,
                    $booking->end_time,
                    ucfirst($booking->status),
                    $booking->notes,
                    $booking->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Show scheduling & booking settings menu
     * 
     * @return View
     */
    public function schedulingMenu(): View
    {
        $trainers = User::where('role', 'trainer')->select('id', 'name')->get();
        return view('admin.bookings.scheduling-menu', compact('trainers'));
    }

    /**
     * Show trainer availability management
     * 
     * @param Request $request
     * @return View
     */
    public function availability(Request $request): View
    {
        $trainers = User::where('role', 'trainer')->select('id', 'name')->get();
        $selectedTrainerId = $request->get('trainer_id', $trainers->first()?->id);
        
        // Get the selected trainer object
        $trainer = null;
        if ($selectedTrainerId) {
            $trainer = User::find($selectedTrainerId);
        }
        
        $availabilities = [];
        if ($selectedTrainerId) {
            $availabilities = Availability::where('trainer_id', $selectedTrainerId)
                ->orderBy('day_of_week')
                ->get()
                ->keyBy('day_of_week');
        }

        $daysOfWeek = [
            1 => 'Monday',
            2 => 'Tuesday', 
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            0 => 'Sunday'
        ];

        return view('admin.bookings.availability', compact('trainers', 'selectedTrainerId', 'trainer', 'availabilities', 'daysOfWeek'));
    }

    /**
     * Update trainer availability
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateAvailability(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|exists:users,id',
            'availability' => 'required|array',
            'availability.*.morning_available' => 'boolean',
            'availability.*.evening_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            foreach ($request->availability as $dayOfWeek => $data) {
                Availability::updateOrCreate(
                    [
                        'trainer_id' => $request->trainer_id,
                        'day_of_week' => $dayOfWeek
                    ],
                    [
                        'morning_available' => $data['morning_available'] ?? false,
                        'evening_available' => $data['evening_available'] ?? false,
                        'morning_start_time' => $data['morning_available'] ? '09:00:00' : null,
                        'morning_end_time' => $data['morning_available'] ? '17:00:00' : null,
                        'evening_start_time' => $data['evening_available'] ? '17:00:00' : null,
                        'evening_end_time' => $data['evening_available'] ? '21:00:00' : null,
                    ]
                );
            }

            return redirect()->back()->with('success', 'Availability updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating availability: ' . $e->getMessage());
        }
    }

    /**
     * Show blocked times management
     * 
     * @param Request $request
     * @return View
     */
    public function blockedTimes(Request $request): View
    {
        $trainers = User::where('role', 'trainer')->select('id', 'name')->get();
        $selectedTrainerId = $request->get('trainer_id', $trainers->first()?->id);
        
        // Get the selected trainer object
        $trainer = null;
        if ($selectedTrainerId) {
            $trainer = User::find($selectedTrainerId);
        }
        
        // Get current month for calendar display
        $currentMonth = $request->get('month') ? 
            Carbon::createFromFormat('Y-m', $request->get('month'))->startOfMonth() : 
            Carbon::now()->startOfMonth();
        
        // Generate calendar days for the current month
        $calendarDays = $this->generateCalendarDaysForBlockedTimes($currentMonth, $selectedTrainerId);
        
        $blockedTimes = [];
        if ($selectedTrainerId) {
            $blockedTimes = BlockedTime::where('trainer_id', $selectedTrainerId)
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();
        }

        return view('admin.bookings.blocked-times', compact('trainers', 'selectedTrainerId', 'trainer', 'currentMonth', 'calendarDays', 'blockedTimes'));
    }

    /**
     * Store new blocked time
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeBlockedTime(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            BlockedTime::create($request->all());
            return redirect()->back()->with('success', 'Blocked time added successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error adding blocked time: ' . $e->getMessage());
        }
    }

    /**
     * Remove blocked time
     * 
     * @param int $id
     * @return RedirectResponse
     */
    public function destroyBlockedTime(int $id): RedirectResponse
    {
        try {
            $blockedTime = BlockedTime::findOrFail($id);
            $blockedTime->delete();
            return redirect()->back()->with('success', 'Blocked time removed successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error removing blocked time: ' . $e->getMessage());
        }
    }

    /**
     * Show session capacity management
     * 
     * @param Request $request
     * @return View
     */
    public function sessionCapacity(Request $request): View
    {
        $trainers = User::where('role', 'trainer')->select('id', 'name')->get();
        $selectedTrainerId = $request->get('trainer_id', $trainers->first()?->id);
        
        // Get the selected trainer object
        $trainer = null;
        if ($selectedTrainerId) {
            $trainer = User::find($selectedTrainerId);
        }
        
        $sessionCapacity = null;
        if ($selectedTrainerId) {
            $sessionCapacity = SessionCapacity::where('trainer_id', $selectedTrainerId)->first();
        }

        return view('admin.bookings.session-capacity', compact('trainers', 'selectedTrainerId', 'trainer', 'sessionCapacity'));
    }

    /**
     * Update session capacity
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSessionCapacity(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|exists:users,id',
            'max_daily_sessions' => 'required|integer|min:1|max:24',
            'max_weekly_sessions' => 'required|integer|min:1|max:168',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            SessionCapacity::updateOrCreate(
                ['trainer_id' => $request->trainer_id],
                [
                    'max_daily_sessions' => $request->max_daily_sessions,
                    'max_weekly_sessions' => $request->max_weekly_sessions,
                ]
            );

            return redirect()->back()->with('success', 'Session capacity updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating session capacity: ' . $e->getMessage());
        }
    }

    /**
     * Show booking approval settings
     * 
     * @param Request $request
     * @return View
     */
    public function bookingApproval(Request $request): View
    {
        $trainers = User::where('role', 'trainer')->select('id', 'name')->get();
        $selectedTrainerId = $request->get('trainer_id', $trainers->first()?->id);
        
        // Get the selected trainer object
        $trainer = null;
        if ($selectedTrainerId) {
            $trainer = User::find($selectedTrainerId);
        }
        
        $bookingSettings = null;
        if ($selectedTrainerId) {
            $bookingSettings = BookingSetting::where('trainer_id', $selectedTrainerId)->first();
        }

        return view('admin.bookings.booking-approval', compact('trainers', 'selectedTrainerId', 'trainer', 'bookingSettings'));
    }

    /**
     * Update booking approval settings
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateBookingApproval(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|exists:users,id',
            'allow_self_booking' => 'boolean',
            'require_approval' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            BookingSetting::updateOrCreate(
                ['trainer_id' => $request->trainer_id],
                [
                    'allow_self_booking' => $request->has('allow_self_booking'),
                    'require_approval' => $request->has('require_approval'),
                ]
            );

            return redirect()->back()->with('success', 'Booking approval settings updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating booking approval settings: ' . $e->getMessage());
        }
    }

    /**
     * Display the schedule calendar view
     * 
     * @param Request $request
     * @return View
     */
    public function schedule(Request $request): View
    {
        $trainers = User::where('role', 'trainer')->get();
        $clients = User::where('role', 'client')->get();
        
        return view('admin.bookings.schedule', compact('trainers', 'clients'));
    }

    /**
     * Get events for Full Calendar in JSON format
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEvents(Request $request)
    {
        $query = Schedule::with(['trainer:id,name', 'client:id,name']);

        // Filter by trainer if specified
        if ($request->filled('trainer_id')) {
            $query->where('trainer_id', $request->trainer_id);
        }

        // Filter by date range if specified
        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('date', [
                Carbon::parse($request->start)->format('Y-m-d'),
                Carbon::parse($request->end)->format('Y-m-d')
            ]);
        }

        $schedules = $query->get();

        $events = $schedules->map(function ($schedule) {
            $startDateTime = Carbon::parse($schedule->date . ' ' . $schedule->start_time);
            $endDateTime = Carbon::parse($schedule->date . ' ' . $schedule->end_time);
            
            // Enhanced color coding based on status and trainer
            $statusColors = [
                'confirmed' => '#28a745',
                'pending' => '#ffc107',
                'cancelled' => '#dc3545',
            ];
            
            // Trainer-specific color variations
            $trainerColors = [
                '#007bff', '#6f42c1', '#e83e8c', '#fd7e14', 
                '#20c997', '#17a2b8', '#6c757d', '#343a40'
            ];
            
            // Get base color from status
            $baseColor = $statusColors[$schedule->status] ?? '#6c757d';
            
            // If filtering by specific trainer, use trainer-specific colors for better distinction
            if (request()->filled('trainer_id')) {
                $color = $baseColor;
            } else {
                // Use trainer-specific colors when showing all trainers
                $trainerIndex = $schedule->trainer_id % count($trainerColors);
                $trainerColor = $trainerColors[$trainerIndex];
                
                // Blend status and trainer colors for better visual distinction
                $color = $schedule->status === 'confirmed' ? $trainerColor : $baseColor;
            }

            return [
                'id' => $schedule->id,
                'title' => $schedule->trainer->name . ' - ' . $schedule->client->name,
                'start' => $startDateTime->toISOString(),
                'end' => $endDateTime->toISOString(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'trainer_id' => $schedule->trainer_id,
                    'trainer_name' => $schedule->trainer->name,
                    'client_id' => $schedule->client_id,
                    'client_name' => $schedule->client->name,
                    'status' => $schedule->status,
                    'notes' => $schedule->notes,
                    'date' => $schedule->date,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time
                ]
            ];
        });

        return response()->json($events);
    }

    /**
     * Create a new booking event via AJAX
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trainer_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:users,id',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDateTime = Carbon::parse($request->start);
            $endDateTime = Carbon::parse($request->end);

            // Check for conflicts
            $conflictingBooking = Schedule::where('trainer_id', $request->trainer_id)
                ->where('date', $startDateTime->format('Y-m-d'))
                ->where('status', '!=', Schedule::STATUS_CANCELLED)
                ->where(function ($query) use ($startDateTime, $endDateTime) {
                    $query->where(function ($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<=', $startDateTime->format('H:i'))
                          ->where('end_time', '>', $startDateTime->format('H:i'));
                    })->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<', $endDateTime->format('H:i'))
                          ->where('end_time', '>=', $endDateTime->format('H:i'));
                    })->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '>=', $startDateTime->format('H:i'))
                          ->where('end_time', '<=', $endDateTime->format('H:i'));
                    });
                })
                ->exists();

            if ($conflictingBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Time slot conflicts with existing booking'
                ], 409);
            }

            $schedule = Schedule::create([
                'trainer_id' => $request->trainer_id,
                'client_id' => $request->client_id,
                'date' => $startDateTime->format('Y-m-d'),
                'start_time' => $startDateTime->format('H:i'),
                'end_time' => $endDateTime->format('H:i'),
                'status' => Schedule::STATUS_PENDING,
                'notes' => $request->notes,
            ]);

            $schedule->load(['trainer:id,name', 'client:id,name']);

            // Create Google Calendar event if booking is confirmed
            $googleEventResult = null;
            if ($schedule->status === Schedule::STATUS_CONFIRMED) {
                $googleEventResult = $schedule->createGoogleCalendarEvent();
            }

            $eventData = [
                'id' => $schedule->id,
                'title' => $schedule->trainer->name . ' - ' . $schedule->client->name,
                'start' => $startDateTime->toISOString(),
                'end' => $endDateTime->toISOString(),
                'backgroundColor' => '#ffc107',
                'borderColor' => '#ffc107',
                'extendedProps' => [
                    'trainer_id' => $schedule->trainer_id,
                    'trainer_name' => $schedule->trainer->name,
                    'client_id' => $schedule->client_id,
                    'client_name' => $schedule->client->name,
                    'status' => $schedule->status,
                    'notes' => $schedule->notes,
                    'date' => $schedule->date,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time
                ]
            ];

            // Add Google Calendar info if event was created
            if ($googleEventResult) {
                $eventData['extendedProps']['meet_link'] = $googleEventResult['meet_link'] ?? null;
                $eventData['extendedProps']['google_event_id'] = $googleEventResult['google_event_id'] ?? null;
            }

            $message = 'Booking created successfully';
            if ($schedule->status === Schedule::STATUS_CONFIRMED) {
                if ($googleEventResult) {
                    $message .= ' with Google Calendar event and Meet link';
                } else {
                    $message .= ' (Google Calendar event could not be created)';
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'event' => $eventData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing booking event via AJAX
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEvent(Request $request, int $id)
    {
        $schedule = Schedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'trainer_id' => 'sometimes|exists:users,id',
            'client_id' => 'sometimes|exists:users,id',
            'start' => 'sometimes|date',
            'end' => 'sometimes|date|after:start',
            'status' => 'sometimes|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [];

            if ($request->has('start') && $request->has('end')) {
                $startDateTime = Carbon::parse($request->start);
                $endDateTime = Carbon::parse($request->end);
                
                $updateData['date'] = $startDateTime->format('Y-m-d');
                $updateData['start_time'] = $startDateTime->format('H:i');
                $updateData['end_time'] = $endDateTime->format('H:i');

                // Check for conflicts when moving the event
                $conflictingBooking = Schedule::where('trainer_id', $request->trainer_id ?? $schedule->trainer_id)
                    ->where('date', $startDateTime->format('Y-m-d'))
                    ->where('id', '!=', $id)
                    ->where('status', '!=', Schedule::STATUS_CANCELLED)
                    ->where(function ($query) use ($startDateTime, $endDateTime) {
                        $query->where(function ($q) use ($startDateTime, $endDateTime) {
                            $q->where('start_time', '<=', $startDateTime->format('H:i'))
                              ->where('end_time', '>', $startDateTime->format('H:i'));
                        })->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                            $q->where('start_time', '<', $endDateTime->format('H:i'))
                              ->where('end_time', '>=', $endDateTime->format('H:i'));
                        })->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                            $q->where('start_time', '>=', $startDateTime->format('H:i'))
                              ->where('end_time', '<=', $endDateTime->format('H:i'));
                        });
                    })
                    ->exists();

                if ($conflictingBooking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Time slot conflicts with existing booking'
                    ], 409);
                }
            }

            if ($request->has('trainer_id')) {
                $updateData['trainer_id'] = $request->trainer_id;
            }

            if ($request->has('client_id')) {
                $updateData['client_id'] = $request->client_id;
            }

            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }

            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            $schedule->update($updateData);
            $schedule->load(['trainer:id,name', 'client:id,name']);

            $color = match($schedule->status) {
                'confirmed' => '#28a745',
                'pending' => '#ffc107',
                'cancelled' => '#dc3545',
                default => '#6c757d'
            };

            return response()->json([
                'success' => true,
                'message' => 'Booking updated successfully',
                'event' => [
                    'id' => $schedule->id,
                    'title' => $schedule->trainer->name . ' - ' . $schedule->client->name,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'trainer_id' => $schedule->trainer_id,
                        'trainer_name' => $schedule->trainer->name,
                        'client_id' => $schedule->client_id,
                        'client_name' => $schedule->client->name,
                        'status' => $schedule->status,
                        'notes' => $schedule->notes,
                        'date' => $schedule->date,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a booking event via AJAX
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteEvent(int $id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Booking deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate calendar days for the given month
     * 
     * @param Carbon $month
     * @return array
     */
    /**
     * Generate calendar days for blocked times view with blocked status
     * 
     * @param Carbon $month The month to generate calendar for
     * @param int|null $trainerId The trainer ID to check blocked times for
     * @return array Array of calendar days with blocked status
     */
    private function generateCalendarDaysForBlockedTimes(Carbon $month, ?int $trainerId = null): array
    {
        $calendarDays = [];
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $today = now()->format('Y-m-d');
        
        // Get blocked dates for the trainer if provided
        $blockedDates = [];
        if ($trainerId) {
            $blockedTimes = BlockedTime::where('trainer_id', $trainerId)
                ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->pluck('date')
                ->toArray();
            $blockedDates = array_unique($blockedTimes);
        }
        
        // Get the first day of the week (Sunday = 0, Monday = 1, etc.)
        $startOfWeek = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);
        
        $currentDate = $startOfWeek->copy();
        
        while ($currentDate <= $endOfWeek) {
            $isCurrentMonth = $currentDate->month === $month->month;
            $isToday = $currentDate->format('Y-m-d') === $today;
            $isBlocked = in_array($currentDate->format('Y-m-d'), $blockedDates);
            
            $calendarDays[] = [
                'day' => $currentDate->day,
                'date' => $currentDate->format('Y-m-d'),
                'isOtherMonth' => !$isCurrentMonth,
                'isToday' => $isToday,
                'isBlocked' => $isBlocked,
                'isSelected' => false, // Can be modified based on selected date logic
            ];
            
            $currentDate->addDay();
        }
        
        return $calendarDays;
    }

    private function generateCalendarDays(Carbon $month): array
    {
        $calendarDays = [];
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $today = now()->format('Y-m-d');
        
        // Get the first day of the week (Sunday = 0, Monday = 1, etc.)
        $startOfWeek = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);
        
        $currentDate = $startOfWeek->copy();
        
        while ($currentDate <= $endOfWeek) {
            $isCurrentMonth = $currentDate->month === $month->month;
            $isToday = $currentDate->format('Y-m-d') === $today;
            
            $calendarDays[] = [
                'day' => $currentDate->day,
                'date' => $currentDate->format('Y-m-d'),
                'isOtherMonth' => !$isCurrentMonth,
                'isToday' => $isToday,
                'isSelected' => false, // Can be modified based on selected date logic
            ];
            
            $currentDate->addDay();
        }
        
        return $calendarDays;
    }

    /**
     * Display trainers scheduling overview
     * 
     * @param Request $request
     * @return View
     */
    public function trainersScheduling(Request $request): View
    {
        // Get all trainers
        $trainers = User::where('role', 'trainer')
            ->with([
                'availabilities',
                'sessionCapacity',
                'bookingSettings',
                'blockedTimes'
            ])
            ->select('id', 'name', 'email', 'phone', 'profile_image', 'created_at')
            ->orderBy('name')
            ->get();

        // Calculate statistics
        $stats = [
            'total_trainers' => $trainers->count(),
            'active_trainers' => $trainers->count(), // All trainers are considered active since no status column exists
            'inactive_trainers' => 0, // No inactive trainers since no status column exists
            'with_availability' => $trainers->filter(function ($trainer) {
                return $trainer->availabilities->count() > 0;
            })->count(),
            'with_session_capacity' => $trainers->filter(function ($trainer) {
                return $trainer->sessionCapacity !== null;
            })->count(),
            'with_booking_settings' => $trainers->filter(function ($trainer) {
                return $trainer->bookingSettings !== null;
            })->count(),
            'total_blocked_times' => BlockedTime::whereIn('trainer_id', $trainers->pluck('id'))->count(),
        ];

        // Get blocked times count for each trainer
        $blockedTimesCount = BlockedTime::selectRaw('trainer_id, COUNT(*) as count')
            ->whereIn('trainer_id', $trainers->pluck('id'))
            ->groupBy('trainer_id')
            ->pluck('count', 'trainer_id');

        // Enhance trainers data with additional info
        $trainers = $trainers->map(function ($trainer) use ($blockedTimesCount) {
            $trainer->blocked_times_count = $trainer->blockedTimes->count();
            $trainer->availability_days_count = $trainer->availabilities->count();
            
            // Check if trainer has complete scheduling setup
            $trainer->has_complete_setup = $trainer->availabilities->count() > 0 && 
                                         $trainer->sessionCapacity !== null && 
                                         $trainer->bookingSettings !== null;
            
            // Add setup status for display
            if ($trainer->has_complete_setup) {
                $trainer->setup_status_text = 'Complete';
                $trainer->setup_status_class = 'status-complete';
            } elseif ($trainer->availabilities->count() > 0 || $trainer->sessionCapacity !== null || $trainer->bookingSettings !== null) {
                $trainer->setup_status_text = 'Partial';
                $trainer->setup_status_class = 'status-partial';
            } else {
                $trainer->setup_status_text = 'Not Set';
                $trainer->setup_status_class = 'status-incomplete';
            }
            
            // Calculate last scheduling update
            $lastUpdates = collect([
                $trainer->availabilities->max('updated_at'),
                $trainer->sessionCapacity?->updated_at,
                $trainer->bookingSettings?->updated_at
            ])->filter()->max();
            
            $trainer->last_scheduling_update = $lastUpdates ? \Carbon\Carbon::parse($lastUpdates) : null;
            
            return $trainer;
        });

        // Update stats with proper calculations
        $stats['complete_setup'] = $trainers->where('has_complete_setup', true)->count();
        $stats['partial_setup'] = $trainers->filter(function ($trainer) {
            return !$trainer->has_complete_setup && 
                   ($trainer->availabilities->count() > 0 || $trainer->sessionCapacity !== null || $trainer->bookingSettings !== null);
        })->count();
        $stats['no_setup'] = $trainers->filter(function ($trainer) {
            return $trainer->availabilities->count() == 0 && 
                   $trainer->sessionCapacity === null && 
                   $trainer->bookingSettings === null;
        })->count();
        $stats['total_blocked_times'] = $trainers->sum('blocked_times_count');

        return view('admin.trainers-scheduling.index', compact('trainers', 'stats'));
    }

    /**
     * Show the Google Calendar booking form
     */
    public function googleCalendarBooking()
    {
        $trainers = User::where('role', 'trainer')
            // ->whereNotNull('email_verified_at')
            ->select('id', 'name', 'email')
            ->get();

        $clients = User::where('role', 'client')
            // ->whereNotNull('email_verified_at')
            ->select('id', 'name', 'email')
            ->get();

        return view('admin.bookings.google-calendar-booking', compact('trainers', 'clients'));
    }

    /**
     * Check trainer's Google Calendar connection status
     */
    public function checkTrainerGoogleConnection($trainerId)
    {
        try {
            $trainer = User::findOrFail($trainerId);
            
            if ($trainer->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a trainer'
                ], 400);
            }

            $googleController = new \App\Http\Controllers\GoogleController();
            $connectionStatus = $googleController->getTrainerConnectionStatus($trainer);

            return response()->json([
                'success' => true,
                'connected' => $connectionStatus['connected'],
                'email' => $connectionStatus['email'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking Google Calendar connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trainer's available time slots from Google Calendar or local availability
     */
    public function getTrainerAvailableSlots(Request $request)
    {
        $request->validate([
            'trainer_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        try {
            $trainer = User::findOrFail($request->trainer_id);
            
            if ($trainer->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a trainer'
                ], 400);
            }

            // Check if trainer has Google Calendar connected
            $googleController = new \App\Http\Controllers\GoogleController();
            $connectionStatus = $googleController->getTrainerConnectionStatus($trainer);

            if ($connectionStatus['connected']) {
                // Use Google Calendar for availability
                try {
                    $googleCalendarService = new \App\Services\GoogleCalendarService();
                    $availableSlots = $googleCalendarService->getAvailableSlots(
                        $trainer,
                        $request->start_date,
                        $request->end_date
                    );
                } catch (\Exception $e) {
                    // If Google Calendar fails, fall back to local availability
                    \Log::warning('Google Calendar failed, falling back to local availability', [
                        'trainer_id' => $trainer->id,
                        'error' => $e->getMessage()
                    ]);
                    $availableSlots = $this->getLocalAvailableSlots($trainer, $request->start_date, $request->end_date);
                }
            } else {
                // Use local availability system when Google Calendar is not connected
                $availableSlots = $this->getLocalAvailableSlots($trainer, $request->start_date, $request->end_date);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'available_slots' => $availableSlots
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching available slots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store Google Calendar booking
     */
    public function storeGoogleCalendarBooking(Request $request)
    {
        $request->validate([
            'trainer_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:users,id',
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'session_type' => 'required|string|in:personal_training,consultation,assessment,follow_up',
            'notes' => 'nullable|string'
        ]);

        try {
            $trainer = User::findOrFail($request->trainer_id);
            $client = User::findOrFail($request->client_id);

            // Verify roles
            if ($trainer->role !== 'trainer') {
                return redirect()->back()->withErrors(['trainer_id' => 'Selected user is not a trainer.']);
            }

            if ($client->role !== 'client') {
                return redirect()->back()->withErrors(['client_id' => 'Selected user is not a client.']);
            }

            // Check if trainer has Google Calendar connected
            $googleController = new \App\Http\Controllers\GoogleController();
            $connectionStatus = $googleController->getTrainerConnectionStatus($trainer);

            if (!$connectionStatus['connected']) {
                return redirect()->back()->withErrors(['trainer_id' => 'Trainer does not have Google Calendar connected.']);
            }

            // Create datetime objects
            $sessionDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->booking_date . ' ' . $request->start_time);
            $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->booking_date . ' ' . $request->end_time);

            // Check for conflicting bookings
            $conflictingBooking = Schedule::where('trainer_id', $trainer->id)
                ->where('date', $request->booking_date)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                          ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                          ->orWhere(function ($q) use ($request) {
                              $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $request->end_time);
                          });
                })
                ->first();

            if ($conflictingBooking) {
                return redirect()->back()->withErrors(['booking_date' => 'Trainer already has a booking during this time slot.']);
            }

            // Create the booking
            $schedule = Schedule::create([
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
                'date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => 'confirmed',
                'notes' => $request->notes,
                'session_type' => $request->session_type
            ]);

            // Create Google Calendar event
            try {
                $googleCalendarService = new \App\Services\GoogleCalendarService();
                $eventData = $googleCalendarService->createEvent($schedule);

                return redirect()->route('admin.bookings.index')
                    ->with('success', 'Booking created successfully with Google Calendar event and Meet link!');
            } catch (\Exception $e) {
                // If Google Calendar event creation fails, still keep the booking but notify
                Log::error('Failed to create Google Calendar event for booking ' . $schedule->id . ': ' . $e->getMessage());
                
                return redirect()->route('admin.bookings.index')
                    ->with('warning', 'Booking created successfully, but failed to create Google Calendar event: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Error creating Google Calendar booking: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the booking: ' . $e->getMessage()]);
        }
    }

    /**
     * Get local available slots for a trainer based on weekly availability
     * 
     * @param User $trainer
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getLocalAvailableSlots(User $trainer, string $startDate, string $endDate): array
    {
        $startDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);
        
        // Get trainer's weekly availability
        $weeklyAvailability = \App\Models\Availability::where('trainer_id', $trainer->id)
            ->get()
            ->keyBy('day_of_week');

        // Get blocked times for the date range
        $blockedTimes = \App\Models\BlockedTime::where('trainer_id', $trainer->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('date');

        // Get existing bookings for the date range
        $existingBookings = \App\Models\Schedule::where('trainer_id', $trainer->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('status', '!=', 'cancelled')
            ->get()
            ->groupBy('date');

        $availableSlots = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek;
            
            // Get weekly availability for this day
            $dayAvailability = $weeklyAvailability->get($dayOfWeek);
            
            if ($dayAvailability) {
                $timeSlots = [];
                
                // Generate morning slots if available
                if ($dayAvailability->morning_start_time && $dayAvailability->morning_end_time) {
                    $morningSlots = $this->generateTimeSlots(
                        $dayAvailability->morning_start_time,
                        $dayAvailability->morning_end_time,
                        60 // 60 minutes per slot
                    );
                    $timeSlots = array_merge($timeSlots, $morningSlots);
                }
                
                // Generate evening slots if available
                if ($dayAvailability->evening_start_time && $dayAvailability->evening_end_time) {
                    $eveningSlots = $this->generateTimeSlots(
                        $dayAvailability->evening_start_time,
                        $dayAvailability->evening_end_time,
                        60 // 60 minutes per slot
                    );
                    $timeSlots = array_merge($timeSlots, $eveningSlots);
                }
                
                // Filter out blocked times and existing bookings
                $dayBlockedTimes = $blockedTimes->get($dateString, collect());
                $dayBookings = $existingBookings->get($dateString, collect());
                
                foreach ($timeSlots as $slot) {
                    $slotStart = \Carbon\Carbon::createFromFormat('H:i', $slot['start_time']);
                    $slotEnd = \Carbon\Carbon::createFromFormat('H:i', $slot['end_time']);
                    $slotDateTime = $currentDate->copy()->setTime($slotStart->hour, $slotStart->minute);
                    
                    // Skip past slots
                    if ($slotDateTime->lt(\Carbon\Carbon::now())) {
                        continue;
                    }
                    
                    $isAvailable = true;
                    
                    // Check against blocked times
                    foreach ($dayBlockedTimes as $blockedTime) {
                        $blockedStart = \Carbon\Carbon::createFromFormat('H:i:s', $blockedTime->start_time);
                        $blockedEnd = \Carbon\Carbon::createFromFormat('H:i:s', $blockedTime->end_time);
                        
                        if ($slotStart->lt($blockedEnd) && $slotEnd->gt($blockedStart)) {
                            $isAvailable = false;
                            break;
                        }
                    }
                    
                    // Check against existing bookings
                    if ($isAvailable) {
                        foreach ($dayBookings as $booking) {
                            $bookingStart = \Carbon\Carbon::createFromFormat('H:i:s', $booking->start_time);
                            $bookingEnd = \Carbon\Carbon::createFromFormat('H:i:s', $booking->end_time);
                            
                            if ($slotStart->lt($bookingEnd) && $slotEnd->gt($bookingStart)) {
                                $isAvailable = false;
                                break;
                            }
                        }
                    }
                    
                    if ($isAvailable) {
                        $availableSlots[] = [
                            'start' => $slotDateTime->toISOString(),
                            'end' => $slotDateTime->copy()->addHour()->toISOString(),
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'date' => $dateString,
                            'display' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A')
                        ];
                    }
                }
            }
            
            $currentDate->addDay();
        }

        return $availableSlots;
    }

    /**
     * Generate time slots for a given time range
     * 
     * @param string $startTime
     * @param string $endTime
     * @param int $slotDuration Duration in minutes
     * @return array
     */
    private function generateTimeSlots(string $startTime, string $endTime, int $slotDuration = 60): array
    {
        $slots = [];
        $start = \Carbon\Carbon::createFromFormat('H:i:s', $startTime);
        $end = \Carbon\Carbon::createFromFormat('H:i:s', $endTime);
        
        while ($start->lt($end)) {
            $slotEnd = $start->copy()->addMinutes($slotDuration);
            
            if ($slotEnd->lte($end)) {
                $slots[] = [
                    'start_time' => $start->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'duration_minutes' => $slotDuration
                ];
            }
            
            $start->addMinutes($slotDuration);
        }
        
        return $slots;
    }
}