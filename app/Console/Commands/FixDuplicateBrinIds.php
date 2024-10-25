<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\License;
use App\Models\Seat;
use App\Models\User;
use App\Models\Privilege;
use App\Models\Stream;
use App\Models\Group;

class FixDuplicateBrinIds extends Command
{
  protected $signature = 'ef:fix:brin-ids';
  protected $description = 'Clean up licenses with duplicate brin_id values';

  public function __construct()
  {
    parent::__construct();
  }

  public function handle()
  {
    $duplicates = DB::select("
      select brin_id, count(*) as n from licenses
      where brin_id
      group by brin_id having n > 1
    ");
    $nduplicates = count($duplicates);
    if ($nduplicates === 0) {
      echo "no duplicates\n";
    }
    foreach ($duplicates as $duplicate) {
      DB::transaction(function() use ($duplicate) {

        $licenses = License::where('brin_id', $duplicate->brin_id)->get();
        $nlicenses = count($licenses);
        echo "\nbrin_id={$duplicate->brin_id}\n";

        for ($i = 1; $i < $nlicenses; ++$i) {

          $license = $licenses[$i];
          echo "license_id={$license->id}\n";

          $canremove = TRUE;
          foreach ($license->groups as $group) {
            $nseats = count($group->seats);
            $ncollections = count($group->collections);
            if ($nseats > 0 || $ncollections > 0) {
              echo "group_name={$group->name}: #seats={$nseats} #collections={$ncollections}\n";
              $canremove = FALSE;
            }
          }

          if ($canremove) {

            foreach ($license->groups as $group) {
              echo "group {$group->id} {$group->name}\n";
              $group->delete();
            }
            foreach ($license->seats as $seat) {
              foreach ($seat->privileges as $privilege) {
                echo "privilege {$privilege->id} {$privilege->action}\n";
                $privilege->delete();
              }
              echo "seat {$seat->id} {$seat->user->email} {$seat->user->role}\n";
              $seat->delete();
            }
            echo "license {$license->id} {$license->description}\n";
            $license->delete();
          }
        }
      });
    }
    echo "ok\n";
  }
}
