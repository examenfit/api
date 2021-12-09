<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\License;
use App\Models\Seat;
use App\Models\User;
use App\Models\Group;

class FixDemoAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:fix:demo-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix privileges';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      $this->fix1();
      $this->fix2();
      //$this->fix3();
    }

    function fix1()
    {
      $users = User::query()
        ->whereNull('link')
        ->where('role', 'docent')
        ->get();
      foreach($users as $user) {
        if (!$user->link) {
          $user->link = Str::random(20);
          $user->save();
          $this->info("{$user->link} {$user->email}");
        }
      }
    }

    function fix2() {
      $seats = Seat::query()
        ->whereNotNull('user_id')
        ->where('role', 'leerling')
        ->get();
      foreach($seats as $seat) {
        $user = $seat->user;
        $examenfit = str_ends_with($user->email, '@examenfit.nl');
        $leerling = str_starts_with($user->email, 'leerling-');
        if ($examenfit && $leerling && !$user->link) {
          foreach($seat->license->seats as $seat) {
            if ($seat->role === 'docent') {
              $user->link = $seat->user->link;
              $user->save();
              $this->info("{$user->link} {$user->email}");
            }
          }
        }
      }
    }

    function fix3() {
      $skip = [];
      $docenten = Seat::query()
        ->where('role', 'docent')
        ->get();
      foreach($docenten as $docent) {
        if (array_key_exists($docent->user->email, $skip)) {
          $this->info("{$docent->user->email} skipping");
          continue;
        } else {
          $skip[$docent->user->email] = true;
        }
        $demo = false;
        foreach($docent->license->seats as $seat) {
          if ($seat->role === 'leerling' && $seat->user && $seat->user->link) {
            $this->info("{$docent->user->email} ok");
          }
        }
        if (!$demo) {
          $this->info($docent->user->email);
          $license = $docent->license;
          $group = Group::firstWhere('license_id', $license->id);
          if ($group) {
            $demo = User::create([
                'first_name' => 'Demo leerling',
                'last_name' => '',
                'role' => 'leerling',
                'email' => 'leerling-'.Str::random(6).'@examenfit.nl',
                'password' => '',
                'link' => $docent->user->link
            ]);
            $seat = Seat::create([
              'license_id' => $license->id,
              'role' => 'leerling',
              'first_name' => 'Demo leerling',
              'user_id' => $demo->id
            ]);
          } else {
            $this->info('no group');
          }
        }
      }
    }
    
}
