<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Seat;
use App\Models\Privilege;

class FixPrivileges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:fix:privileges';

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
    }

    public function fix1()
    {
      $seats = Seat::query()
        ->where('role', 'leerling')
        ->get();
      foreach($seats as $seat) {
        $count = 0;
        foreach($seat->privileges as $priv) {
          if ($priv->action === 'oefensets uitvoeren') {
            $count++;
          }
        }
        if ($count) {
          continue;
        }
        if ($seat->user_id) {
          $this->info('user.email: '.$seat->user->email);
        } else if ($seat->email) {
          $this->info('seat.email: '.$seat->email);
        } else {
          $this->info('seat: #'.$seat->id);
        }
        $license = $seat->license;
        $this->info('license: #'.$license->id);
        foreach($license->seats as $docent) {
          if($docent->role === 'docent') {
            if ($docent->user_id) {
              $this->info(' (docent) user.email: '.$docent->user->email);
            } else if ($docent->email) {
              $this->info(' (docent) seat.email: '.$docent->email);
            } else {
              $this->info(' (docent) seat: #'.$docent->id);
            }
            foreach($docent->privileges as $priv) {
              if ($priv->object_type === 'stream') {
                $this->info('  priv: stream='.$priv->object_id);
                Privilege::create([
                  'actor_seat_id' => $seat->id,
                  'action' => 'oefensets uitvoeren',
                  'object_type' => $priv->object_type,
                  'object_id' => $priv->object_id,
                  'begin' => $license->begin,
                  'end' => $license->end
                ]);
              }
            }
          }
        }
      }
    }

    function fix2() {
      foreach(Seat::all() as $seat) {
        $this->info('#'.$seat->id);
        $map = [];
        foreach($seat->privileges as $priv) {
          $k = join(';', [$priv->action, $priv->object_type, $priv->object_id]);
          if (array_key_exists($k, $map)) {
            $this->info("delete $k");
            $priv->delete();
          } else {
            $this->info("keep $k");
            $map[$k] = true;
          }
        }
      }
    }
}
