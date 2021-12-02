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

class FixAdminAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:fix:admin-accounts';

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
    }


    function link($link, $first_name, $last_name, $role, $group)
    {
        $user = User::create([
          'email' => Str::random(10).'@examenfit.nl',
          'password' => '',
          'role' => $role,
          'first_name' => $first_name,
          'last_name' => $last_name,
          'link' => $link
        ]);

        $seat = Seat::create([
          'license_id' => 1,
          'user_id' => $user->id,
          'role' => $user->role,
          'first_name' => "Test $role",
        ]);
        $seat->groups()->sync([ $group->id ]);
        $this->info("{$seat->user->email}");

        if ($role === 'docent') {
          $priv = Privilege::create([
            'actor_seat_id' => $seat->id,
            'action' => 'groepen beheren',
            'object_type' => 'group',
            'object_id' => $group->id,
            'begin' => '2001-01-01',
            'end' => '2111-01-01'
          ]);
          $this->info("{$priv->action} {$priv->object_type} {$priv->object_id}");
        }

        $streams = Stream::all();
        $action = $role === 'docent' ? 'onbeperkt opgavensets samenstellen' : 'oefensets uitvoeren';
        foreach($streams as $stream) {
          $priv = Privilege::create([
            'actor_seat_id' => $seat->id,
            'action' => $action,
            'object_type' => 'stream',
            'object_id' => $stream->id,
            'begin' => '2001-01-01',
            'end' => '2111-01-01'
          ]);
          $this->info("{$priv->action} {$priv->object_type} {$priv->object_id}");
        }
    }

    function fix1()
    {
      $license = License::find(1); // ExamenFit company license
      $admins = User::query()
        ->whereNull('link')
        ->where('role', 'admin')
        ->get();
      foreach($admins as $admin) {
        $admin->link = Str::random(20);
        $admin->save();
        $this->info("{$admin->link} {$admin->email}");
        $group = Group::create([
          'license_id' => 1,
          'name' => $admin->email . ' groep',
          'is_active' => 1
        ]);
        $this->link($admin->link, 'Do', 'Cent, van der', 'docent', $group);
        $this->link($admin->link, 'Leo', 'Eerling', 'leerling', $group);
      }
    }
}
