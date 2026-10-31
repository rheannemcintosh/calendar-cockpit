<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            // Existing calendar items and their sub-categories are all exact,
            // so none of them differ from their sub-category's default.
            $table->boolean('time_type_overridden')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            $table->dropColumn('time_type_overridden');
        });
    }
};
