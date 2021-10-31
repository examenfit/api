<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->integer('totalPoints');
            $table->integer('scoredPoints');
            $table->boolean('hasCompletedScoreFlow');
            $table->string('updatedAt');
            $table->json('sections');
            $table->timestamps();
            $table->unique([
              'question_id',
              'user_id',
              'updatedAt'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scores');
    }
}
