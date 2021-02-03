<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStudentAnswerColumnsToAnswerSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('answer_sections', function (Blueprint $table) {
            $table->renameColumn('text', 'correction');
        });

        Schema::table('answer_sections', function (Blueprint $table) {
            $table->text('text')->nullable()->after('correction');
            $table->text('elaboration')->nullable()->after('text');
            $table->text('explanation')->nullable()->after('elaboration');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('answer_sections', function (Blueprint $table) {
            //
        });
    }
}
