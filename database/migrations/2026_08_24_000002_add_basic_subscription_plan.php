<?php

use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $plan = config('subscription_plans.user_plans.Basic');

        if (!$plan) {
            return;
        }

        Subscription::updateOrCreate(
            ['type' => 'Basic'],
            [
                'name' => $plan['name'],
                'price' => $plan['price'],
                'duration_days' => $plan['duration_value'],
                'duration_unit' => $plan['duration_unit'],
                'type' => $plan['type'],
                'payment_status' => $plan['payment_status'],
                'badge' => $plan['badge'],
                'features' => $plan['features'],
                'status' => 'active',
            ]
        );
    }

    public function down(): void
    {
        Subscription::where('type', 'Basic')->delete();
    }
};
