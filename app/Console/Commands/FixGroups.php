<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\License;
use App\Models\Group;
use App\Models\Privilege;

class FixGroups extends Command
{
    protected $signature = 'ef:fix:groups';
    protected $description = 'Fix privileges';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
      $this->fixLicenseIds();
      $this->fixPrivileges();
      $this->deleteEmptyGroups();
    }

    function fixLicenseIds() {
      foreach(License::all() as $license) {
        $groups = $this->getInvalidGroups($license);
        if (count($groups)) {
          $this->fixLicenseId($license, $groups);
        }
      }
    }

    function getInvalidGroups($license) {
      $groups = [];
      foreach($license->seats as $seat) {
        foreach($seat->groups as $group) {
          if ($group->license_id !== $license->id) {
            $groups[$group->id] = $group;
          }
        }
      }
      return array_values($groups);
    }

    function fixLicenseId($license, $groups) {
      $owner = $this->getDocent($license);
      $this->info("license={$license->id} {$license->description}");
      foreach($groups as $group) {
        $fixed = $this->createGroup($owner, $group->name);
        $this->info("  group={$fixed->id} {$fixed->name}");
        foreach($license->seats as $seat) {
          foreach($seat->groups as $seatGroup) {
            if ($seatGroup->id === $group->id) {
              $seat->groups()->sync([ $fixed->id ]);
                $this->info("    seat={$seat->id} email={$seat->email}");
            }
          }
        }
      }
    }

    function getDocent($license) {
      foreach($license->seats as $seat) {
        if ($seat->role === 'docent') {
          return $seat;
        }
      }
    }

    function createGroup($owner, $name) {
      $license = $owner->license;
      $group = Group::create([
        'name' => $name,
        'license_id' => $license->id,
        'is_active' => TRUE
      ]);
      return $group;
    }

    function fixPrivileges() {
      foreach(Group::all() as $group) {
        $count = $this->getPrivilegeCount($group);
        if (!$count) {
          $this->fixGroupPrivileges($group);
        }
      }
    }

    function getPrivilegeCount($group) {
      $count = Privilege::query()
        ->where('object_type', 'group')
        ->where('object_id', $group->id)
        ->count();
      return $count;
    }

    function fixGroupPrivileges($group) {
      $license = $group->license;
      $owner = $this->getDocent($license);
      $this->info("group={$group->id} {$group->name} owner={$owner->id} {$owner->email} priv='groepen beheren'");
      $privilege = Privilege::create([
        'actor_seat_id' => $owner->id,
        'action' => 'groepen beheren',
        'object_type' => 'group',
        'object_id' => $group->id,
        'begin' => $license->begin,
        'end' => $license->end,
      ]);
    }

    function deleteEmptyGroups() {
      foreach(Group::all() as $group) {
        $empty = $this->isEmptyGroup($group);
        if ($empty) {
          $this->deleteGroup($group);
        }
      }
    }

    function isEmptyGroup($group) {
      $count = count($group->seats);
      return $count === 0;
    }

    function deleteGroup($group) {
      $this->info("group={$group->id} {$group->name} delete");
      $this->deleteGroupPrivileges($group);
      $group->delete();
    }

    function deleteGroupPrivileges($group) {
      Privilege::query()
        ->where('object_type', 'group')
        ->where('object_id', $group->id)
        ->delete();
    }
}
