<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->integer('time_before')->default(0)->after('duration')->comment('Minutes before service');
            $table->integer('time_after')->default(0)->after('time_before')->comment('Minutes after service');
            $table->integer('booking_scope_days')->default(30)->after('time_after')->comment('Days ahead customers can book');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['time_before', 'time_after', 'booking_scope_days']);
        });
    }
};
