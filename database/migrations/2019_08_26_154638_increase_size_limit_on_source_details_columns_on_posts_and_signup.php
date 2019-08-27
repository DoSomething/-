<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IncreaseSizeLimitOnSourceDetailsColumnsOnPostsAndSignup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('signups', function (Blueprint $table) {
            $table->string('source_details', 1024)->change();            
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('source_details', 1024)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('signups', function (Blueprint $table) {
            $table->string('source_details', 255)->change();    
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('source_details', 255)->change();
        });
    }
}
