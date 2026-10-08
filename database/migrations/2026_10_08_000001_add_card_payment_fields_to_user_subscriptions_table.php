<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_subscriptions', 'card_holder_name')) {
                $table->string('card_holder_name')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('user_subscriptions', 'card_last_four')) {
                $table->string('card_last_four', 4)->nullable()->after('card_holder_name');
            }
            if (!Schema::hasColumn('user_subscriptions', 'card_brand')) {
                $table->string('card_brand', 32)->nullable()->after('card_last_four');
            }
            if (!Schema::hasColumn('user_subscriptions', 'card_expiry')) {
                $table->string('card_expiry', 7)->nullable()->after('card_brand');
            }
            if (!Schema::hasColumn('user_subscriptions', 'card_payment_ref')) {
                $table->string('card_payment_ref', 64)->nullable()->after('card_expiry');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $columns = [
                'card_holder_name',
                'card_last_four',
                'card_brand',
                'card_expiry',
                'card_payment_ref',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('user_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
