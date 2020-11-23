<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingExamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('set null');
            $table->string('level')->nullable();
            $table->integer('year')->nullable();
            $table->integer('term')->nullable();
            $table->string('assignment_file_path')->nullable();
            $table->string('appendix_file_path')->nullable();
            $table->string('correction_requirement_file_path')->nullable();
            $table->string('standardization_url')->nullable();
            $table->json('assignment_contents')->nullable();
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
        Schema::dropIfExists('incoming_exams');
    }
}
