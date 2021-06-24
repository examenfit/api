<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameLevelIdColumnsToStreamId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->renameColumn('level_id', 'stream_id');
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->renameColumn('level_id', 'stream_id');
        });
        Schema::table('domains', function (Blueprint $table) {
            $table->renameColumn('level_id', 'stream_id');
        });
        Schema::table('question_types', function (Blueprint $table) {
            $table->renameColumn('level_id', 'stream_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('streams_id', function (Blueprint $table) {
            //
        });
    }
}
