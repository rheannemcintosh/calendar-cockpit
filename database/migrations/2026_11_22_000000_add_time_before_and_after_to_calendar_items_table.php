<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            // In minutes. The defaults keep every existing calendar item without any.
            $table->unsignedSmallInteger('minutes_before')->default(0);
            $table->unsignedSmallInteger('minutes_after')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            $table->dropColumn(['minutes_before', 'minutes_after']);
        });
    }
};
