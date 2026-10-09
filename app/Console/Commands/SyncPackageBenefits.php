<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserSubscription;
use App\Services\SubscriptionAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncPackageBenefits extends Command
{
    protected $signature = 'packages:sync-benefits';

    protected $description = 'Grant daily package super-likes, auto-boost active VIP/VVIP, and clear top placement when a plan expires.';

    public function handle(SubscriptionAccessService $access): int
    {
        if (!Schema::hasColumn('users', 'package_likes_total')) {
            $this->warn('users.package_likes_total is missing. Run migrations first.');

            return self::FAILURE;
        }

        $cleared = $this->clearExpiredBoosts();
        $likesGranted = 0;
        $boostsApplied = 0;
        $boostsRenewed = 0;

        User::query()
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'verified')
                    ->whereNull('cancelled_at')
                    ->where(function ($sub) {
                        $sub->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->whereHas('plan', function ($plan) {
                        $plan->whereIn('type', ['VIP', 'VVIP'])->where('price', '>', 0);
                    });
            })
            ->with(['subscriptions' => function ($q) {
                $q->with('plan')
                    ->where('status', 'verified')
                    ->whereNull('cancelled_at')
                    ->where(function ($sub) {
                        $sub->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->latest();
            }])
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($access, &$likesGranted, &$boostsApplied, &$boostsRenewed) {
                foreach ($users as $user) {
                    try {
                        $likesGranted += $access->grantDailyPackageLikes($user);
                        $result = $access->syncAutoBoost($user);
                        if ($result === 'applied') {
                            $boostsApplied++;
                        } elseif ($result === 'renewed') {
                            $boostsRenewed++;
                        }
                    } catch (\Throwable $e) {
                        Log::error('Package benefits sync failed for user.', [
                            'user_id' => $user->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Cleared expired boosts: {$cleared}");
        $this->info("Package likes granted: {$likesGranted}");
        $this->info("VIP boosts applied: {$boostsApplied}");
        $this->info("VVIP boosts renewed: {$boostsRenewed}");

        return self::SUCCESS;
    }

    private function clearExpiredBoosts(): int
    {
        $paidUserIds = UserSubscription::query()
            ->where('status', 'verified')
            ->whereNull('cancelled_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereHas('plan', function ($q) {
                $q->whereIn('type', ['VIP', 'VVIP'])->where('price', '>', 0);
            })
            ->pluck('user_id');

        $query = User::query()->whereNotNull('profile_boost_until');
        if ($paidUserIds->isNotEmpty()) {
            $query->whereNotIn('id', $paidUserIds);
        }

        return $query->update(['profile_boost_until' => null]);
    }
}
