<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrivilegesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('privileges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuer_user_id')->nullable();
            $table->foreign('issuer_user_id')->references('id')->on('users'); // issuer
            $table->foreignId('actor_seat_id');
            $table->foreign('actor_seat_id')->references('id')->on('seats');
            $table->string('action');
            $table->string('object_type')->nullable();
            $table->integer('object_id')->nullable();
            $table->date('begin');
            $table->date('end');
            $table->boolean('is_active')->default(1);
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
        Schema::dropIfExists('privileges');
    }
}
