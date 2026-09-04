<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('user_subscriptions', 'original_price')) {
            return;
        }

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('amount_payable', 10, 2)->nullable();
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->string('discount_reason')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('user_subscriptions', 'original_price')) {
            return;
        }

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'original_price',
                'amount_payable',
                'discount_percent',
                'discount_reason',
            ]);
        });
    }
};
