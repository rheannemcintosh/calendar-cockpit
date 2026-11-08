<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('alter table flags drop constraint flags_icon_check');
        DB::statement("alter table flags add constraint flags_icon_check check (icon in ('pound-sterling', 'lock', 'star', 'heart', 'bell', 'flag', 'circle-alert', 'clock', 'calendar-check', 'ticket', 'plane', 'gift', 'users', 'briefcase', 'house', 'car', 'handshake', 'credit-card', 'receipt', 'shield', 'sparkles', 'zap', 'pin', 'bookmark'))");
    }

    /**
     * Flags with an icon that was added here are given the star, so the old
     * check can be added again.
     */
    public function down(): void
    {
        DB::table('flags')->whereNotIn('icon', ['pound-sterling', 'lock', 'star'])->update(['icon' => 'star']);

        DB::statement('alter table flags drop constraint flags_icon_check');
        DB::statement("alter table flags add constraint flags_icon_check check (icon in ('pound-sterling', 'lock', 'star'))");
    }
};
