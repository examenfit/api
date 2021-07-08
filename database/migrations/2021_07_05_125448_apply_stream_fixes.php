<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Http\Controllers\StreamController;

class ApplyStreamFixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $controller = new StreamController();
        $controller->fix_domains();
        $controller->fix_tags();
        $controller->fix_null_stream_chapters();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
