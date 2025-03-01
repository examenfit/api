<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Seat;
use App\Models\Stream;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixDoubleSeats extends Command {

  protected $signature = 'ef:fix:double-seats';
  protected $description = 'Merge duplicate seats into one per user per license';

  public function __construct() {
    parent::__construct();
  }

  /** 
    * Handle console command
    *
    * @return int 0 = success
    */
  public function handle() {
    $seats = $this->getSeats();
    if (count($seats) === 0) {
      $this->info("no duplicate seats found");
    }
    foreach($seats as $seat) {
      $duplicates = $this->getDuplicates($seat);
      $this->removeDuplicates($duplicates);
    }
  }

  function getSeats() {
    return DB::select("
      SELECT
        license_id,
        user_id,
        count(*) AS duplicateCount
      FROM seats
      WHERE
        user_id IS NOT NULL
      GROUP BY user_id, license_id
      HAVING duplicateCount > 1
      ORDER BY license_id, user_id
    ");
  }

  function getDuplicates($seat) {
    return Seat::where([
      'license_id' => $seat->license_id,
      'user_id' => $seat->user_id,
    ])->orderBy('created_at', 'DESC')->get();
  }

  function removeDuplicates($duplicates) {
    $seat = $duplicates[0];
    foreach($duplicates as $duplicate) {
      if ($duplicate-> id !== $seat->id) {
        $this->removeDuplicate($duplicate, $seat);
      }
    }
  }

  function removeDuplicate($duplicate, $seat) {
    $this->info("remove Seat#{$duplicate->id}: {$duplicate->license->description} <{$duplicate->user->email}> {$duplicate->created_at}");
    $this->repairSeatGroups($duplicate, $seat);
    $this->repairPrivileges($duplicate, $seat);
    $this->deleteSeat($duplicate);
  }

  // repair seat groups

  function repairSeatGroups($duplicate, $seat) {
    foreach ($duplicate->groups as $group) {
      $this->repairSeatGroup($group, $duplicate, $seat);
    }
  }

  function repairSeatGroup($group, $duplicate, $seat) {
    foreach ($seat->groups as $existing) {
      $exists = $group->id === $existing->id;
      if ($exists) {
        return $this->deleteSeatGroup($group, $duplicate);
      }
    }
    $this->moveSeatGroup($group, $duplicate, $seat);
  }

  function deleteSeatGroup($group, $duplicate) {
    $this->info("  delete SeatGroup#{duplicate->id} {$group->name}");
    DB::delete("
      DELETE FROM seat_group WHERE
        group_id = ? AND
        seat_id = ?
    ", [
      $group->id,
      $duplicate->id,
    ]);
  }

  function moveSeatGroup($group, $duplicate, $seat) {
    $this->info("  move SeatGroup#{$seat->id} {$group->name}");
    DB::update("
      UPDATE seat_group SET
        seat_id = ?
      WHERE
        group_id = ? AND
        seat_id = ?
    ", [
      $seat->id,
      $group->id,
      $duplicate->id,
    ]);
  }

  // repair privileges

  function repairPrivileges($duplicate, $seat) {
    foreach ($duplicate->privileges as $privilege) {
      $this->repairPrivilege($privilege, $duplicate, $seat);
    }
  }

  function repairPrivilege($privilege, $duplicate, $seat) {
    foreach ($seat->privileges as $existing) {
      $exists = $privilege->action === $existing->action
              && $privilege->object_id === $existing->object_id
              && $privilege->object_type === $existing->object_type;
      if ($exists) {
        return $this->deletePrivilege($privilege, $duplicate);
      }
    }
    $this->movePrivilege($privilege, $duplicate, $seat);
  }

  function deletePrivilege($privilege, $duplicate) {
    $info = $this->getPrivilegeInfo($privilege);
    $this->info("  delete Privilege#{$duplicate->id} {$privilege->action} {$info}");
    $privilege->delete();
  }

  function movePrivilege($privilege, $duplicate, $seat) {
    $info = $this->getPrivilegeInfo($privilege);
    $this->info("  move Privilege#{$seat->id} {$privilege->action} {$info}");
    $privilege->actor_seat_id = $seat->id;
    $privilege->save();
  }

  function getPrivilegeInfo($privilege) {
    if ($privilege->object_type === 'stream') {
      $stream = Stream::find($privilege->object_id);
      return $stream->slug;
    }
    if ($privilege->object_type === 'collection') {
      $collection = Collection::find($privilege->object_id);
      return $collection->name;
    }
  }

  function deleteSeat($seat) {
    $this->info("  delete Seat#{$seat->id}");
    $seat->delete();
  }
}
