<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Schedule;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TrainerInvoiceController extends ApiBaseController
{
    public function index()
    {
        $invoices = Invoice::where('trainer_id', Auth::id())->latest()->paginate(20);
        return response()->json(['success' => true, 'invoices' => $invoices]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:users,id',
            'currency' => 'nullable|string|size:3',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'notes' => 'required|string',
            'items' => 'nullable|array',
            // 'items.*.workout_id' => 'nullable|integer|exists:workouts,id',
            'items.*.title' => 'nullable|string',
            'items.*.amount' => 'nullable|numeric|min:0',
            'items.*.qty' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $data = $validator->validated();

        $invoice = Invoice::create([
            'trainer_id' => Auth::id(),
            'client_id' => $data['client_id'],
            'currency' => $data['currency'] ?? 'USD',
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'created_by' => 'trainer',
        ]);

        $total = isset($data['amount']) ? (float) $data['amount'] : 0.0;

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $title = $item['title'] ?? null;
                if (!$title && !empty($item['workout_id'])) {
                    $w = Workout::find($item['workout_id']);
                    $title = $w ? $w->name : 'Workout';
                }
                $qty = $item['qty'] ?? 1;
                $amount = (float) $item['amount'];
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'workout_id' => $item['workout_id'] ?? null,
                    'title' => $title,
                    'amount' => $amount,
                    'qty' => $qty,
                ]);
                $total += $amount * $qty;
            }
        } else {
            $workouts = Workout::where('user_id', Auth::id())
                ->whereHas('assignments', function ($q) use ($data) {
                    $q->where('assigned_to', $data['client_id'] ?? null)
                      ->where('assigned_to_type', 'client')
                      ->whereIn('status', ['assigned', 'in_progress']);
                })
                ->get();

            foreach ($workouts as $w) {
                $price = $w->price ?? 0;
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'workout_id' => $w->id,
                    'title' => $w->name,
                    'amount' => $price,
                    'qty' => 1,
                ]);
                $total += $price;
            }
        }

        $invoice->update(['total_amount' => $total]);

        return response()->json(['success' => true, 'invoice' => $invoice->load('items')]);
    }

    public function getClientInvoiceItems(int $clientId, Request $request)
    {
        $trainerId = Auth::id();

        $workouts = Workout::where('user_id', $trainerId)
            ->whereHas('assignments', function ($q) use ($clientId) {
                $q->where('assigned_to', $clientId)
                  ->where('assigned_to_type', 'client')
                  ->whereIn('status', ['assigned', 'in_progress']);
            })
            ->with('assignments')
            ->get();

        $workoutItems = $workouts->map(function ($w) {
            return [
                'type' => 'workout',
                'workout_id' => $w->id,
                'title' => $w->name,
                'amount' => (float) ($w->price ?? 0),
                'qty' => 1,
                'metadata' => [
                    'formatted_price' => $w->formatted_price,
                    'duration' => $w->duration,
                ],
            ];
        })->values();

        $sessions = Schedule::query()
            ->where('trainer_id', $trainerId)
            ->where('client_id', $clientId)
            ->whereIn('status', [Schedule::STATUS_CONFIRMED])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $sessionItems = $sessions->map(function ($s) {
            return [
                'type' => 'session',
                'schedule_id' => $s->id,
                'title' => $s->meeting_agenda ?? 'Training Session',
                'amount' => 0.0,
                'qty' => 1,
                'metadata' => [
                    'date' => $s->date,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'session_type' => $s->session_type,
                ],
            ];
        })->values();

        $items = $workoutItems->toBase()->merge($sessionItems->toBase())->values();

        return response()->json([
            'success' => true,
            'items' => $items,
            'summary' => [
                'workouts_count' => $workoutItems->count(),
                'sessions_count' => $sessionItems->count(),
                'workouts_total' => $workoutItems->sum('amount'),
            ],
        ]);
    }
}

