<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Workout;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\WorkoutAssignment;


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
     * Create an invoice for the authenticated trainer and given client,
     * using the provided workout IDs as line items.
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createInvoice(Request $request)
    {
        // Validate with explicit JSON error response to avoid HTML redirects
        try
        {
            $request->validate([
                'client_id' => 'required|integer|exists:users,id',
                'workout_ids' => 'required|array|min:1',
                'workout_ids.*' => 'integer|exists:workouts,id',
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
        $workoutIds = $request->input('workout_ids');

        // Verify selected workouts are assigned by trainer to this client
        $eligibleWorkoutIds = WorkoutAssignment::query()
            ->where('assigned_by', $trainerId)
            ->where('assigned_to', $clientId)
            ->where('assigned_to_type', 'client')
            ->pluck('workout_id')
            ->unique();

        $selectedIds = collect($workoutIds)->map(fn ($id) => (int) $id);

        if (!$selectedIds->every(fn ($id) => $eligibleWorkoutIds->contains($id)))
        {
            return response()->json([
                'success' => false,
                'message' => 'One or more workouts are not assigned to this client by you.',
            ], 422);
        }

        // Fetch workouts and ensure they belong to the authenticated trainer
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

        try
        {
            DB::beginTransaction();

            $total = $workouts->sum(fn ($w) => (float) $w->price);

            $invoice = Invoice::create([
                'trainer_id' => $trainerId,
                'client_id' => $clientId,
                'total_amount' => round($total, 2),
                'status' => 'unpaid',
            ]);

            foreach ($workouts as $workout)
            {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'workout_id' => $workout->id,
                    'title' => $workout->name,
                    'amount' => round((float) $workout->price, 2),
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

        try
        {
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
        }
        catch (\Throwable $e)
        {
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