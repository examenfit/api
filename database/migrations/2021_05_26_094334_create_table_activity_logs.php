<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableActivityLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('examenfit_session');
            $table->string('activity');
            $table->foreignId('collection_id')->nullable();
            $table->foreign('collection_id')->references('id')->on('collections');
            $table->foreignId('question_id')->nullable();
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreignId('topic_id')->nullable();
            $table->foreign('topic_id')->references('id')->on('topics');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_activity_log');
    }
}
