<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnnotationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('streams')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('annotations')->onDelete('cascade');
            $table->string('type');
            $table->string('name');
            $table->integer('position')->nullable();
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
        Schema::dropIfExists('annotations');
    }
}
