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
            // All nullable: a location is optional, so existing calendar
            // items simply have none.
            $table->string('location_name')->nullable();
            $table->string('location_address')->nullable();
            $table->decimal('location_latitude', 10, 7)->nullable();
            $table->decimal('location_longitude', 10, 7)->nullable();
        });

        // A location is a named place with a point on the map, so either all
        // of name, latitude and longitude are set or none of them are. The
        // address is optional because not every place has one. The explicit
        // "is not null" checks matter: a check constraint lets a row through
        // when its condition is unknown, which "null between ..." would be.
        DB::statement('alter table calendar_items add constraint calendar_items_location_check check ('
            .'(location_name is null and location_address is null and location_latitude is null and location_longitude is null)'
            .' or (location_name is not null and location_latitude is not null and location_longitude is not null'
            .' and location_latitude between -90 and 90 and location_longitude between -180 and 180)'
            .')');
    }

    public function down(): void
    {
        DB::statement('alter table calendar_items drop constraint calendar_items_location_check');

        Schema::table('calendar_items', function (Blueprint $table) {
            $table->dropColumn(['location_name', 'location_address', 'location_latitude', 'location_longitude']);
        });
    }
};
