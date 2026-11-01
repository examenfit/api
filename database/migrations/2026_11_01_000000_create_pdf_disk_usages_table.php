<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pdf_disk_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('percentage');
            $table->unsignedInteger('used_mb')->nullable();
            $table->unsignedInteger('available_mb')->nullable();
            $table->timestamp('alerted_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pdf_disk_usages');
    }
};
