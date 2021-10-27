<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id');
            $table->foreign('license_id')->references('id')->on('licenses');
            $table->string('role');
            $table->string('email')->nullable();
            $table->string('token')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->json('settings')->nullable();
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
        Schema::dropIfExists('seats');
    }
}
