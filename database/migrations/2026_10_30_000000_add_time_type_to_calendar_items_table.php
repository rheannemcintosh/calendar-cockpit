<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            // A plain string rather than enum(), which on SQL Server adds a
            // check constraint that would need a migration for each new time
            // type. The default fills in every existing calendar item.
            $table->string('time_type')->default('exact');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            $table->dropColumn('time_type');
        });
    }
};
