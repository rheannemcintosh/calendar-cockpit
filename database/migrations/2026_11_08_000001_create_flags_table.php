<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('icon');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        // Not enum(), for the same reason as calendar_items_time_type_check:
        // a named constraint can be dropped and re-added to change the icons.
        DB::statement("alter table flags add constraint flags_icon_check check (icon in ('pound-sterling', 'lock', 'star'))");

        Schema::create('calendar_item_flag', function (Blueprint $table) {
            $table->foreignId('calendar_item_id')->constrained()->cascadeOnDelete();
            // SQL Server refuses a second cascade here: these rows would be
            // reachable from users along two cascade paths. Flag::delete()
            // takes a flag off its calendar items instead.
            $table->foreignId('flag_id')->index()->constrained()->noActionOnDelete();

            $table->primary(['calendar_item_id', 'flag_id']);
        });

        // Every existing user starts with the default flags, one at a time so
        // their ids keep the order they are shown in.
        $now = now();

        DB::table('users')->orderBy('id')->pluck('id')->each(function (int $userId) use ($now): void {
            foreach ([['Pre-Paid', 'pound-sterling'], ['Non-Cancellable', 'lock'], ['Important', 'star']] as [$name, $icon]) {
                DB::table('flags')->insert([
                    'user_id' => $userId,
                    'name' => $name,
                    'icon' => $icon,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_item_flag');
        Schema::dropIfExists('flags');
    }
};
