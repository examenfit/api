<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionAnnotationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_annotation', function (Blueprint $table) {
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->foreignId('annotation_id')->constrained('annotations')->onDelete('cascade');
            $table->unique([
              'question_id',
              'annotation_id'
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
        Schema::dropIfExists('question_annotation');
    }
}
