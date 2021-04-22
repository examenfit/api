<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppendixesToQuestionDependencyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_dependency', function (Blueprint $table) {
            $table->boolean('appendixes')->default(false)->after('attachments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_dependency', function (Blueprint $table) {
            //
        });
    }
}
