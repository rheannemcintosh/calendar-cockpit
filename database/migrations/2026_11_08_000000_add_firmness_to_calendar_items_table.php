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
            // The default makes every existing calendar item confirmed.
            $table->string('firmness')->default('confirmed');
        });

        // Not enum(), for the same reason as calendar_items_time_type_check:
        // a named constraint can be dropped and re-added to change the values.
        DB::statement("alter table calendar_items add constraint calendar_items_firmness_check check (firmness in ('confirmed', 'tentative', 'idea'))");
    }

    public function down(): void
    {
        DB::statement('alter table calendar_items drop constraint calendar_items_firmness_check');

        Schema::table('calendar_items', function (Blueprint $table) {
            $table->dropColumn('firmness');
        });
    }
};
