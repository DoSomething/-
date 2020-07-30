<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLocationToActionStatsAndGroupsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('action_stats', function (Blueprint $table) {
            $table->string('location', 6)->nullable()->after('school_id');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->string('location', 6)->nullable()->after('state');
        });

        // Execute SQL query to find all groups.
        foreach (DB::table('groups')->select('id','state')->get() as $result) {
            if (! $result->state) {
                continue;
            }
            // Execute update query to populate location.
            DB::table('groups')
                ->where('id', $result->id)
                ->update(['location' => 'US-' . $result->state]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('action_stats', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
}
