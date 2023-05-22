<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarkupColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->text('introductionHtml');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->text('introductionHtml');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->text('textHtml');
        });
        Schema::table('streams', function (Blueprint $table) {
            $table->text('formulebladHtml');
        });
        Schema::table('answer_sections', function (Blueprint $table) {
            $table->text('correctionHtml');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
