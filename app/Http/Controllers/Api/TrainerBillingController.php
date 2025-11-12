<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Workout;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Schedule;
use App\Models\WorkoutAssignment;
use App\Models\TrainerStripeAccount;


/**
 * TrainerBillingController
 * 
 * API endpoints for trainers to manage workouts-based invoicing.
 * - List client workouts
 * - Create invoices from selected workouts
 * - List trainer invoices
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Controllers
 * @category    API
 * @author      Dev Team
 * @since       1.0.0
 */
class TrainerBillingController extends Controller
{
    /**
     * List workouts for a given client belonging to the authenticated trainer.
     *
     * @param  int $clientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function listClientWorkouts(int $clientId)
    {
        $trainerId = Auth::id();

        // Fetch workouts assigned by the trainer to the given client
        $assignments = WorkoutAssignment::query()
            ->where('assigned_by', $trainerId)
            ->where('assigned_to', $clientId)
            ->where('assigned_to_type', 'client')
            ->with(['workout' => function ($q) {
                $q->select('id', 'name', 'price', 'user_id');
            }])
            ->get();

        // Map to workouts, ensure the workout belongs to the trainer
        $workouts = $assignments
            ->map(function ($assignment) use ($trainerId) {
                $w = $assignment->workout;
                return ($w && (int)$w->user_id === (int)$trainerId) ? $w : null;
            })
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'name' => $w->name,
                    'price' => (float) $w->price,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $workouts,
        ]);
    }

    /**
     * Create an invoice for the authenticated trainer and given client.
     *
     * Validates inputs to match UI:
     * - client with optional assigned workout and session bookings
     * - amount (required)
     * - due date (required)
     * - note (required)
     *
     * When workouts are provided, they are validated to be assigned-to client by this trainer
     * and added as invoice line items. When session bookings are provided, they are validated
     * to belong to both trainer and client and added as descriptive line items.
     * Invoice total_amount uses the provided amount value.
     *
     * @param  Request $request Incoming request with invoice body
     * @return \Illuminate\Http\JsonResponse JSON response with invoice or validation errors
     */
    public function createInvoice(Request $request)
    {
        // Validate with explicit JSON error response to avoid HTML redirects
        try
        {
            $request->validate([
                'client_id' => 'required|integer|exists:users,id',
                'amount' => 'required|numeric|min:0.01',
                'due_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
                'note' => 'required|string|max:1000',
                'workout_ids' => 'nullable|array',
                'workout_ids.*' => 'integer|exists:workouts,id',
                'booking_ids' => 'nullable|array',
                'booking_ids.*' => 'integer|exists:schedules,id',
            ]);
        }
        catch (ValidationException $e)
        {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invoice creation request.',
                'errors' => $e->errors(),
            ], 422);
        }

        $trainerId = Auth::id();
        $clientId = (int) $request->input('client_id');
        $workoutIds = $request->input('workout_ids', []);
        $bookingIds = $request->input('booking_ids', []);
        $amount = (float) $request->input('amount');
        $dueDate = $request->input('due_date');
        $note = $request->input('note');

        // Verify selected workouts are assigned by trainer to this client (when provided)
        $selectedIds = collect($workoutIds)->map(fn ($id) => (int) $id);
        if ($selectedIds->isNotEmpty())
        {
            $eligibleWorkoutIds = WorkoutAssignment::query()
                ->where('assigned_by', $trainerId)
                ->where('assigned_to', $clientId)
                ->where('assigned_to_type', 'client')
                ->pluck('workout_id')
                ->unique();

            if (!$selectedIds->every(fn ($id) => $eligibleWorkoutIds->contains($id)))
            {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more workouts are not assigned to this client by you.',
                ], 422);
            }
        }

        // Fetch workouts and ensure they belong to the authenticated trainer
        $workouts = collect();
        if ($selectedIds->isNotEmpty())
        {
            $workouts = Workout::query()
                ->whereIn('id', $selectedIds)
                ->select(['id', 'name', 'price', 'user_id'])
                ->get()
                ->filter(fn ($w) => (int)$w->user_id === (int)$trainerId)
                ->values();

            if ($workouts->isEmpty())
            {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching workouts found for this trainer/client.',
                ], 422);
            }
        }

        // Validate optional booking_ids belong to trainer and client
        $bookings = collect();
        if (!empty($bookingIds))
        {
            $bookings = Schedule::query()
                ->whereIn('id', $bookingIds)
                ->where('trainer_id', $trainerId)
                ->where('client_id', $clientId)
                ->select(['id', 'date', 'session_type'])
                ->get();

            $providedBookingCount = count($bookingIds);
            if ($bookings->count() !== $providedBookingCount)
            {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more session bookings are invalid for this trainer/client.',
                ], 422);
            }
        }

        try
        {
            DB::beginTransaction();
            $invoice = Invoice::create([
                'trainer_id' => $trainerId,
                'client_id' => $clientId,
                'total_amount' => round($amount, 2),
                'status' => 'unpaid',
                'due_date' => $dueDate,
                'note' => $note,
            ]);

            // Add workout-based line items (if provided)
            foreach ($workouts as $workout)
            {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'workout_id' => $workout->id,
                    'title' => $workout->name,
                    'amount' => round((float) $workout->price, 2),
                ]);
            }

            // Add session booking line items (descriptive; amount left at 0 unless pricing exists)
            foreach ($bookings as $booking)
            {
                $title = sprintf('Session: %s (%s)',
                    ucwords(str_replace('_', ' ', (string) $booking->session_type)),
                    optional($booking->date)->format('Y-m-d')
                );

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'workout_id' => null,
                    'title' => $title,
                    'amount' => 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $invoice->load('items'),
            ], 201);
        }
        catch (\Throwable $e)
        {
            DB::rollBack();
            Log::error('Invoice creation failed', [
                'trainer_id' => $trainerId,
                'client_id' => $clientId,
                'payload' => [
                    'amount' => $amount,
                    'due_date' => $dueDate,
                    'workout_ids' => $workoutIds,
                    'booking_ids' => $bookingIds,
                ],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice.',
            ], 500);
        }
    }

    /**
     * List trainer invoices
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listInvoices()
    {
        $trainerId = Auth::id();

        $invoices = Invoice::query()
            ->where('trainer_id', $trainerId)
            ->with('items')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Initiate Stripe Connect onboarding for the authenticated trainer.
     * Returns an onboarding URL and account status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function connectStripeAccount()
    {
        $trainerId = Auth::id();

        // If no payload provided, return onboarding URL (existing behavior)
        if (empty(request()->all())) {
            try {
                $service = app(\App\Services\StripeConnectService::class);
                $result = $service->initiateOnboarding($trainerId);

                return response()->json([
                    'success' => $result['success'],
                    'data' => [
                        'account_id' => $result['account_id'],
                        'onboarding_url' => $result['onboarding_url'],
                        'verification_status' => $result['verification_status'],
                    ],
                    'message' => $result['success'] ? 'Stripe Connect onboarding initiated.' : ($result['error'] ?? 'Failed to initiate onboarding'),
                ], $result['success'] ? 200 : 422);
            } catch (\Throwable $e) {
                Log::error('Trainer Stripe connect failed', [
                    'trainer_id' => $trainerId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initiate Stripe Connect onboarding.',
                ], 500);
            }
        }

        // Validate onboarding payload (identity, bank, business) per UI steps
        $payload = request()->all();

        $rules = [
            'identity.full_name' => ['required', 'string', 'min:2'],
            'identity.date_of_birth' => ['required', 'string'],
            'identity.address_line1' => ['required', 'string'],
            'identity.address_line2' => ['nullable', 'string'],
            'identity.city' => ['required', 'string'],
            'identity.state' => ['required', 'string'],
            'identity.postal_code' => ['required', 'string'],
            'identity.country' => ['nullable', 'string', 'size:2'],
            'identity.phone' => ['required', 'string'],
            'identity.email' => ['required', 'email'],

            'bank.account_holder_name' => ['required', 'string'],
            'bank.bank_name' => ['nullable', 'string'],
            'bank.routing_number' => ['required', 'digits_between:4,9'],
            'bank.account_number' => ['required', 'digits_between:4,17'],

            'business.business_name' => ['required', 'string'],
            'business.business_address_line1' => ['required', 'string'],
            'business.business_address_line2' => ['nullable', 'string'],
            'business.business_city' => ['required', 'string'],
            'business.business_state' => ['required', 'string'],
            'business.business_postal_code' => ['required', 'string'],
            'business.business_country' => ['nullable', 'string', 'size:2'],
            'business.business_type' => ['required', 'string', 'in:individual,sole_proprietorship,company'],
            'business.business_phone' => ['nullable', 'string'],
            'business.business_email' => ['nullable', 'email'],
            'business.tax_id_ein' => ['nullable', 'string'],
        ];

        $validator = Validator::make($payload, $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for bank connect onboarding.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Parse date of birth: support MM/DD/YYYY or YYYY-MM-DD
        $dobInput = $payload['identity']['date_of_birth'];
        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dobInput)) {
                $dob = Carbon::createFromFormat('m/d/Y', $dobInput)->format('Y-m-d');
            } else {
                $dob = Carbon::parse($dobInput)->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format for date of birth. Use MM/DD/YYYY or YYYY-MM-DD.',
            ], 422);
        }

        $identity = $payload['identity'];
        $business = $payload['business'];
        $bank = $payload['bank'];

        $accountHolderType = $business['business_type'] === 'company' ? 'company' : 'individual';
        $country = $identity['country'] ?? 'US';

        try {
            // Attach bank account via Stripe Connect
            $service = app(\App\Services\StripeConnectService::class);
            $attachResult = $service->attachExternalBankAccount($trainerId, [
                'account_holder_name' => $bank['account_holder_name'],
                'routing_number' => $bank['routing_number'],
                'account_number' => $bank['account_number'],
                'account_holder_type' => $accountHolderType,
                'country' => $country,
                'currency' => 'usd',
            ]);

            // Persist identity and business info locally (non-sensitive). Do this even if attach fails.
            $taxIdLast4 = null;
            if (!empty($business['tax_id_ein'])) {
                $taxIdDigits = preg_replace('/\D/', '', (string) $business['tax_id_ein']);
                $taxIdLast4 = strlen($taxIdDigits) >= 4 ? substr($taxIdDigits, -4) : $taxIdDigits;
            }

            $accountLast4 = $attachResult['last4'] ?? (substr((string)$bank['account_number'], -4) ?: null);
            $routingLast4 = $attachResult['routing_last4'] ?? (substr((string)$bank['routing_number'], -4) ?: null);

            $review = [
                'business_information' => [
                    'business_name' => $business['business_name'],
                    'business_type' => $business['business_type'],
                    'business_address' => [
                        'line1' => $business['business_address_line1'],
                        'line2' => $business['business_address_line2'] ?? null,
                        'city' => $business['business_city'],
                        'state' => $business['business_state'],
                        'postal_code' => $business['business_postal_code'],
                        'country' => $business['business_country'] ?? 'US',
                    ],
                    'business_phone' => $business['business_phone'] ?? null,
                    'business_email' => $business['business_email'] ?? null,
                    'tax_id_last4' => $taxIdLast4,
                ],
                'personal_information' => [
                    'full_name' => $identity['full_name'],
                    'date_of_birth' => $dob,
                    'address' => [
                        'line1' => $identity['address_line1'],
                        'line2' => $identity['address_line2'] ?? null,
                        'city' => $identity['city'],
                        'state' => $identity['state'],
                        'postal_code' => $identity['postal_code'],
                        'country' => $country,
                    ],
                    'personal_phone' => $identity['phone'],
                    'personal_email' => $identity['email'],
                ],
                'bank_account_information' => [
                    'account_holder_name' => $bank['account_holder_name'],
                    'bank_name' => $attachResult['bank_name'] ?? ($bank['bank_name'] ?? null),
                    'account_last4' => $accountLast4,
                    'routing_last4' => $routingLast4,
                ],
            ];

            TrainerStripeAccount::updateOrCreate(
                ['trainer_id' => $trainerId],
                [
                    'account_id' => $attachResult['account_id'] ?? null,
                    // Identity
                    'full_name' => $identity['full_name'],
                    'dob' => $dob,
                    'address_line1' => $identity['address_line1'],
                    'address_line2' => $identity['address_line2'] ?? null,
                    'city' => $identity['city'],
                    'state' => $identity['state'],
                    'postal_code' => $identity['postal_code'],
                    'country' => $country,
                    'phone' => $identity['phone'],
                    'email' => $identity['email'],
                    // Business
                    'business_name' => $business['business_name'],
                    'business_type' => $business['business_type'],
                    'business_address_line1' => $business['business_address_line1'],
                    'business_address_line2' => $business['business_address_line2'] ?? null,
                    'business_city' => $business['business_city'],
                    'business_state' => $business['business_state'],
                    'business_postal_code' => $business['business_postal_code'],
                    'business_country' => $business['business_country'] ?? 'US',
                    'business_phone' => $business['business_phone'] ?? null,
                    'business_email' => $business['business_email'] ?? null,
                    'tax_id_last4' => $taxIdLast4,
                    // Bank hints
                    'account_holder_name' => $bank['account_holder_name'],
                    'bank_name' => $attachResult['bank_name'] ?? ($bank['bank_name'] ?? null),
                    'bank_account_last4' => $accountLast4,
                    'routing_number_last4' => $routingLast4,
                    'external_account_id' => $attachResult['external_account_id'] ?? null,
                    'bank_verification_status' => 'pending',
                    'details_submitted_at' => now(),
                    'onboarding_review' => $review,
                ]
            );

            if (!$attachResult['success']) {
                // For non-custom accounts, provide onboarding URL and signal that data is saved.
                $errorData = [];
                if (!empty($attachResult['onboarding_url'])) {
                    $errorData['onboarding_url'] = $attachResult['onboarding_url'];
                }
                if (!empty($attachResult['account_id'])) {
                    $errorData['account_id'] = $attachResult['account_id'];
                }
                $errorData['saved'] = true;
                $errorData['review'] = $review;

                return response()->json([
                    'success' => false,
                    'message' => $attachResult['error'] ?? 'Bank account must be added via Stripe onboarding.',
                    'data' => $errorData,
                ], 422);
            }

            // Also return onboarding link to complete any remaining Stripe checks
            $statusResult = app(\App\Services\StripeConnectService::class)->initiateOnboarding($trainerId);

            return response()->json([
                'success' => true,
                'message' => 'Bank account added and onboarding details saved. Pending micro-deposit verification.',
                'data' => [
                    'account_id' => $attachResult['account_id'],
                    'verification_status' => $statusResult['verification_status'] ?? 'pending',
                    'bank_verification_status' => 'pending',
                    'review' => $review,
                    'onboarding_url' => $statusResult['onboarding_url'] ?? null,
                    'next_steps' => [
                        'micro_deposit_expected' => true,
                        'support_contact' => 'support@trainerhq.com',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Trainer bank connect payload persist failed', [
                'trainer_id' => $trainerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to save bank connect onboarding details.',
            ], 500);
        }
    }

    /**
     * Stripe Connect return URL handler (web route).
     * Verifies the trainer's Stripe account status and updates local record.
     * Returns a simple HTML response so users don’t see a 404.
     *
     * @param  Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stripeConnectReturn(Request $request)
    {
        $trainerId = Auth::id();
        // Fallback to query param for unauthenticated webview/mobile callback
        if (!$trainerId) {
            $trainerIdParam = (int)($request->query('trainer_id') ?? 0);
            if ($trainerIdParam > 0) {
                $trainerId = $trainerIdParam;
            }
        }

        if (!$trainerId) {
            $html = '<!doctype html><html><head><meta charset="utf-8"><title>Stripe Onboarding</title></head><body style="font-family:system-ui;line-height:1.6;padding:24px">'
                . '<h2>Stripe Connect</h2>'
                . '<p>We could not identify your session. Please return to the app and retry the onboarding flow.</p>'
                . '</body></html>';
            return response($html);
        }

        try {
            $service = app(\App\Services\StripeConnectService::class);
            $ensure = $service->ensureAccount($trainerId);
            if (!$ensure['success'] || empty($ensure['account_id'])) {
                return response('Unable to verify Stripe account. Please retry from the app.', 500);
            }

            $secret = config('services.stripe.secret');
            $stripe = new \Stripe\StripeClient($secret);
            $account = $stripe->accounts->retrieve($ensure['account_id']);
            $verificationStatus = ($account->details_submitted ?? false) ? 'verified' : 'pending';

            // Persist status locally
            TrainerStripeAccount::updateOrCreate(
                ['trainer_id' => $trainerId],
                [
                    'account_id' => $ensure['account_id'],
                    'verification_status' => $verificationStatus,
                    'details_submitted_at' => ($account->details_submitted ?? false) ? now() : null,
                ]
            );

            $html = '<!doctype html><html><head><meta charset="utf-8"><title>Stripe Onboarding</title></head><body style="font-family:system-ui;line-height:1.6;padding:24px">'
                . '<h2>Stripe Connect</h2>'
                . '<p>Onboarding status: <strong>' . e($verificationStatus) . '</strong>.</p>'
                . '<p>You can return to the app and continue.</p>'
                . '</body></html>';
            return response($html);
        } catch (\Throwable $e) {
            Log::error('Stripe Connect return handler failed', [
                'trainer_id' => $trainerId,
                'error' => $e->getMessage(),
            ]);
            return response('Failed to process Stripe Connect return. Please retry from the app. Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Stripe Connect refresh URL handler (web route).
     * Regenerates an onboarding link and redirects to Stripe.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stripeConnectRefresh(Request $request)
    {
        $trainerId = Auth::id();
        // Fallback to query param for unauthenticated webview/mobile callback
        if (!$trainerId) {
            $trainerIdParam = (int)($request->query('trainer_id') ?? 0);
            if ($trainerIdParam > 0) {
                $trainerId = $trainerIdParam;
            }
        }

        if (!$trainerId) {
            $html = '<!doctype html><html><head><meta charset="utf-8"><title>Stripe Onboarding</title></head><body style="font-family:system-ui;line-height:1.6;padding:24px">'
                . '<h2>Stripe Connect</h2>'
                . '<p>We could not identify your session. Please return to the app and start onboarding again.</p>'
                . '</body></html>';
            return response($html);
        }
        try {
            $service = app(\App\Services\StripeConnectService::class);
            $result = $service->initiateOnboarding($trainerId);
            if ($result['success'] && !empty($result['onboarding_url'])) {
                return redirect()->away($result['onboarding_url']);
            }
            return response('Unable to refresh Stripe onboarding link. Please try again later.', 500);
        } catch (\Throwable $e) {
            Log::error('Stripe Connect refresh handler failed', [
                'trainer_id' => $trainerId,
                'error' => $e->getMessage(),
            ]);
            return response('Failed to refresh Stripe onboarding link.', 500);
        }
    }

    /**
     * List trainer payout history
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listPayouts()
    {
        $trainerId = Auth::id();

        $payouts = \App\Models\Payout::query()
            ->where('trainer_id', $trainerId)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payouts,
        ]);
    }
}