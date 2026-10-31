<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            // The default fills in every existing sub-category.
            $table->string('default_time_type')->default('exact');
        });

        // Named for the same reason as calendar_items_time_type_check.
        DB::statement("alter table sub_categories add constraint sub_categories_default_time_type_check check (default_time_type in ('exact', 'window', 'block', 'approximate'))");
    }

    public function down(): void
    {
        DB::statement('alter table sub_categories drop constraint sub_categories_default_time_type_check');

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropColumn('default_time_type');
        });
    }
};
