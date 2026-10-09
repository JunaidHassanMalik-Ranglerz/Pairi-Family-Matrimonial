<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_subscriptions', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->default('monthly')->after('status');
            }
        });

        SystemSetting::setVal('annual_billing_discount_percent', 40);
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('user_subscriptions', 'billing_cycle')) {
                $table->dropColumn('billing_cycle');
            }
        });
    }
};
