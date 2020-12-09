<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipsToQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('domain_id')
                ->nullable()
                ->after('topic_id')
                ->constrained('domains')
                ->onDelete(\DB::raw('set null'));

            $table->foreignId('type_id')
                ->nullable()
                ->after('domain_id')
                ->constrained('question_types')
                ->onDelete(\DB::raw('set null'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            //
        });
    }
}
