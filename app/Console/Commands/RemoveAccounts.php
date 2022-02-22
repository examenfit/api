<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\License;
use App\Models\Seat;
use App\Models\User;
use App\Models\Privilege;
use App\Models\Stream;
use App\Models\Group;
use App\Models\Collection;

class RemoveAccounts extends Command {

  protected $signature = 'ef:remove:accounts';
  protected $description = 'Remove accounts';

  public function handle() {
    for (;;) {
      $id = $this->ask('User.id');
      $user = User::find($id);
      $link = $user->link;
      if ($link) {
        $this->removeLinkedUsers($link);
      } else {
        $this->removeUser($user);
      }
    }
  }

  function removeLinkedUsers($link) {
    $this->logLink($link);
    $users = $this->getLinkedUsers($link);
    $this->removeUsers($users);
  }

  function logLink($link) {
    $this->info("User.link: {$link}");
  }

  function removeUsers($users) {
    foreach($users as $user) {
      $this->removeUser($user);
    }
  }

  function getLinkedUsers($link) {
    return User::query()
      ->where('link', $link)
      ->get();
  }

  function removeUser($user) {
    $this->logUser($user);
    $this->removeUserSeats($user);
    $this->removeUserCollections($user);
  }

  function logUser($user) {
    $this->info("User: {$user->first_name} {$user->last_name} <{$user->email}>");
  }

  function removeUserSeats($user) {
    $this->removeSeats($user->seats);
  }

  function removeSeats($seats) {
    foreach($seats as $seat) {
      $this->removeSeat($seat);
    }
  }

  function removeSeat($seat) {
    $this->logSeat($seat);
    $this->leaveGroups($seat);
    $this->removeSeatPrivileges($seat);
    $license = $seat->license;
    $this->logLicense($license);
  }

  function removeSeatPrivileges($seat) {
    $this->removePrivileges($seat->privileges);
  }

  function logLicense($license) {
    $seatCount = count($license->seats);
    $description = $license->description ?: $license->type;
    $this->info("License: {$description}, {$seatCount} seats");
  }

  function logSeat($seat) {
    $this->info("Seat: {$seat->role}");
  }

  function leaveGroups($seat) {
    $groups = $seat->groups;
    $seat->groups()->sync([]);
    $this->cleanupGroups($groups);
  }

  function cleanupGroups($groups) {
    foreach($groups as $group) {
      $this->cleanupGroup($group);
    }
  }

  function cleanupGroup($group) {
    if ($this->isEmptyGroup($group)) {
      $this->removeGroup($group);
    }
  }

  function isEmptyGroup($group) {
    return !count($group->seats);
  }

  function removeGroup($group) {
    $this->logGroup($group);
    $group->delete();
  }

  function logGroup($group) {
    $seatCount = count($group->seats);
    $this->info("Group: {$group->name}");
  }

  function removePrivileges($privileges) {
    foreach($privileges as $privilege) {
      $this->removePrivilege($privilege);
    }
  }

  function removePrivilege($privilege) {
    $this->logPrivilege($privilege);
    $privilege->delete();
  }

  function logPrivilege($privilege) {
    $this->info("Privilege: {$privilege->action} {$privilege->object_type} {$privilege->object_id}");
  }

  function removeUserCollections($user) {
    $collections = Collection::where('user_id', $user->id);
    $this->removeCollections($collections);
  }

  function removeCollections($collections) {
    foreach($collections as $collection) {
      $this->removeCollection($collection);
    }
  }

  function removeCollection($collection) {
    $this->logCollection($collection);
    $collection->delete();
  }

  function logCollection($collection) {
    $this->info("Collection: {$collection->name}");
  }
}

