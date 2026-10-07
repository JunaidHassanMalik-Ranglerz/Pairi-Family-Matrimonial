<?php

namespace Database\Seeders;

use App\Models\MarriageBureauSubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        // App UI shows Free / VIP / VVIP. Keep Basic synced but inactive.
        $visibleTypes = ['Free', 'VIP', 'VVIP'];

        foreach (config('subscription_plans.user_plans') as $plan) {
            Subscription::updateOrCreate(
                ['type' => $plan['type']],
                [
                    'name' => $plan['name'],
                    'price' => $plan['price'],
                    'duration_days' => $plan['duration_value'],
                    'duration_unit' => $plan['duration_unit'],
                    'type' => $plan['type'],
                    'payment_status' => $plan['payment_status'],
                    'badge' => $plan['badge'],
                    'features' => $plan['features'],
                    'status' => in_array($plan['type'], $visibleTypes, true) ? 'active' : 'inactive',
                ]
            );
        }

        Subscription::whereNotIn('type', array_keys(config('subscription_plans.user_plans')))->delete();

        $mb = config('subscription_plans.mb_plan');
        MarriageBureauSubscriptionPlan::query()->delete();
        MarriageBureauSubscriptionPlan::create([
            'name' => $mb['name'],
            'price' => $mb['price'],
            'duration_days' => $mb['duration_value'],
            'duration_unit' => $mb['duration_unit'],
            'type' => $mb['type'],
            'payment_status' => $mb['payment_status'],
            'features' => $mb['features'],
            'status' => 'active',
        ]);
    }
}
