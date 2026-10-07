<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_feature_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('feature', 40);
            $table->string('period_key', 32);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'feature', 'period_key']);
            $table->index(['user_id', 'feature']);
        });

        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('starter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['starter_id', 'recipient_id']);
            $table->index('starter_id');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE profile_interests MODIFY action ENUM('interest', 'pass', 'super_like') NOT NULL DEFAULT 'interest'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
        Schema::dropIfExists('plan_feature_usages');

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE profile_interests MODIFY action ENUM('interest', 'pass') NOT NULL DEFAULT 'interest'");
        }
    }
};
