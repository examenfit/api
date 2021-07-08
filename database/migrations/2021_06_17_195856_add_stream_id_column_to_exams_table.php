<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStreamIdColumnToExamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->after('course_id')->constrained('streams')->onDelete('cascade');
        });
        DB::update("
          update exams, streams, levels
          set stream_id = streams.id
          where streams.course_id = exams.course_id
            and streams.level_id = levels.id
            and exams.level = lower(levels.name)
        ");
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign('exams_course_id_foreign');
            $table->dropColumn('course_id');
            $table->dropColumn('level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exams', function (Blueprint $table) {
            //
        });
    }
}
