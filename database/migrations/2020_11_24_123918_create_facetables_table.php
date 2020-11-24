<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacetablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facetables', function (Blueprint $table) {
            $table->id();
            $table->string('facetable_type');
            $table->foreignId('facetable_id');
            $table->foreignId('facet_id')->constrained('facets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facetables');
    }
}
