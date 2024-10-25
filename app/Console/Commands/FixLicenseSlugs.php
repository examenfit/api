<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FixLicenseSlugs extends Command
{
  protected $signature = 'ef:fix:license-slugs';
  protected $description = 'Copy brin_id into slug';

  public function __construct()
  {
    parent::__construct();
  }

  public function handle()
  {
    $count = DB::update("
      update licenses
      set slug = concat('brin-', brin_id)
      where brin_id > ''
    ");
    echo "ok\n";
  }
}
