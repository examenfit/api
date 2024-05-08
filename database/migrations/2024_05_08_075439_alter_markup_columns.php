<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterMarkupColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->text('introductionHtml')->nullable()->default(null)->change();
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->text('introductionHtml')->nullable()->default(null)->change();
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->text('textHtml')->nullable()->default(null)->change();
        });
        Schema::table('streams', function (Blueprint $table) {
            $table->text('formulebladHtml')->nullable()->default(null)->change();
        });
        Schema::table('answer_sections', function (Blueprint $table) {
            $table->text('correctionHtml')->nullable()->default(null)->change();
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
