<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_items', function (Blueprint $table) {
            // The default fills in every existing calendar item.
            $table->string('time_type')->default('exact');
        });

        // Not enum(): on SQL Server it adds a check constraint with a
        // generated name, which Laravel cannot change later. Naming it here
        // means adding a time type is a drop and re-add of this constraint.
        DB::statement("alter table calendar_items add constraint calendar_items_time_type_check check (time_type in ('exact', 'window', 'block', 'approximate'))");
    }

    public function down(): void
    {
        DB::statement('alter table calendar_items drop constraint calendar_items_time_type_check');

        Schema::table('calendar_items', function (Blueprint $table) {
            $table->dropColumn('time_type');
        });
    }
};
