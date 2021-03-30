<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMorphFieldsToHighlightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('highlights', function (Blueprint $table) {
            // Drop foreign
            $table->dropForeign(['topic_id']);

            $table->string('linkable_type')->after('id')->nullable();
            $table->renameColumn('topic_id', 'linkable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('highlights', function (Blueprint $table) {
            //
        });
    }
}
