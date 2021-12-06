<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\License;
use App\Models\Seat;
use App\Models\User;
use App\Models\Group;

class FixLicenseDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:fix:license-descriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix descriptions';

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

    function fix1()
    {
      foreach(License::all() as $license) {
        foreach($license->seats as $seat) {
          foreach($seat->privileges as $priv) {
            if ($priv->action === 'licentie beheren') {
              $type = $license->type;
              $email = $seat->user->email;
              $license->description = "$type $email";
              $license->save();
              $this->info($license->description);
            }
          }
        }
      }
    }

}
