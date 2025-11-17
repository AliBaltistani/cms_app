<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainerSubscription;
use Illuminate\Http\Request;

class SubscriptionsController extends Controller
{
    public function index(Request $request)
    {
        $subscriptions = TrainerSubscription::with([
            'client:id,name,email,phone',
            'trainer:id,name,email,phone'
        ])->orderBy('created_at', 'desc')->paginate(30);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $subscriptions
            ]);
        }

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function toggle(Request $request, $id)
    {
        $subscription = TrainerSubscription::findOrFail($id);
        $subscription->update([
            'status' => $subscription->status === 'active' ? 'inactive' : 'active',
            'unsubscribed_at' => $subscription->status === 'active' ? now() : null,
            'subscribed_at' => $subscription->status !== 'active' ? now() : $subscription->subscribed_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription status updated',
            'status' => $subscription->status
        ]);
    }
}