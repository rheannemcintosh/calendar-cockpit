<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            // SQL Server refuses null on delete here: calendar items would be
            // reachable from users along two cascade paths. The models clear
            // it instead when a sub-category or its category is deleted.
            $table->foreignId('sub_category_id')->nullable()->index()->constrained()->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            $table->dropForeign(['sub_category_id']);
            $table->dropIndex(['sub_category_id']);
            $table->dropColumn('sub_category_id');
        });
    }
};
