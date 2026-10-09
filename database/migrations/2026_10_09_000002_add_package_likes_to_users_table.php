<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'package_likes_total')) {
                $table->unsignedInteger('package_likes_total')->default(0)->after('profile_boost_until');
            }
            if (!Schema::hasColumn('users', 'package_likes_granted_on')) {
                $table->date('package_likes_granted_on')->nullable()->after('package_likes_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'package_likes_granted_on')) {
                $table->dropColumn('package_likes_granted_on');
            }
            if (Schema::hasColumn('users', 'package_likes_total')) {
                $table->dropColumn('package_likes_total');
            }
        });
    }
};
