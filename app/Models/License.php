<?php

namespace App\Models;

use DateTime;
use DateInterval;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory, HashID;


    public $fillable = [
        'type',
        'begin',
        'end'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public static function createTrialLicense($user, $streams = [ 1, 2 ])
    {
        $begin = new DateTime;
        $end = new DateTime;
        $end->add(new DateInterval('P21D'));

        $license = License::create([
            'type' => 'proeflicentie',
            'begin' => $begin,
            'end' => $end
        ]);

        echo "license #{$license->id}\n";
        echo "user #{$user->id}\n";
 
        $seat = Seat::create([
            'license_id' => $license->id,
            'user_id' => $user->id,
            'role' => 'docent'
        ]);

        echo "license #{$seat->id}\n";

        foreach ($streams as $stream_id) {
            Privilege::create([
                'actor_seat_id' => $seat->id,
                'action' => 'beperkt oefensets samenstellen',
                'object_type' => 'stream',
                'object_id' => $stream_id,
                'begin' => $begin,
                'end' => $end
            ]);
        }

        return $license;
    }
}
