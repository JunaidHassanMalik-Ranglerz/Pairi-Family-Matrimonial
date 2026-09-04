<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        try {
            $types = array_keys(config('subscription_plans.user_plans', ['Free' => [], 'VIP' => [], 'VVIP' => []]));

            return response()->json([
                'success' => 200,
                'subscriptions' => Subscription::whereIn('type', $types)
                    ->orderByRaw("FIELD(type, '".implode("','", $types)."')")
                    ->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscriptions',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Creating new subscription plans is disabled. Only configured user plans exist.',
        ], 403);
    }

    public function show($id)
    {
        try {
            $subscription = Subscription::findOrFail($id);

            return response()->json([
                'success' => 200,
                'subscription' => $subscription,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $subscription = Subscription::findOrFail($id);

            $data = $request->validate([
                'price' => 'required|numeric|min:0',
                'duration_days' => 'required|integer|min:1',
                'duration_unit' => 'required|in:days,months',
            ]);

            if ($subscription->type === 'Free') {
                $data['price'] = 0;
            }

            $subscription->update($data);

            return response()->json([
                'success' => 200,
                'message' => 'Subscription updated successfully',
                'subscription' => $subscription->fresh(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy($id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Subscription plans cannot be deleted.',
        ], 403);
    }
}
