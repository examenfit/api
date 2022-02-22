<?php

use App\Models\Exam;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPositionColumnToTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->integer('position')->nullable();
        });
        $this->fixExams();
    }

    function fixExams() {
        foreach(Exam::all() as $exam) {
            foreach($exam->topics as $topic) {
                $topic->position = $topic->questions[0]->number;
                $topic->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('topics', function (Blueprint $table) {
            //
        });
    }
}
