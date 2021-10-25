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

    public static function createProeflicentie($user, $streams = [ 1, 2 ])
    {
        $begin = new DateTime;
        $end = new DateTime;
        $end->add(new DateInterval('P21D'));

        $license = License::create([
            'type' => 'proeflicentie',
            'begin' => $begin,
            'end' => $end
        ]);

        $seat = Seat::create([
            'license_id' => $license->id,
            'user_id' => $user->id,
            'role' => 'docent'
        ]);

        Privilege::create([
            'actor_seat_id' => $seat->id,
            'action' => 'licentie beheren',
            'object_type' => 'license',
            'object_id' => $license->id,
            'begin' => $begin,
            'end' => $end
        ]);

        // $group = Group::create("leerlingen");

        //Privilege::create([
        //    'actor_seat_id' => $seat->id,
        //    'action' => 'groepen beheren',
        //    'object_type' => 'group',
        //    'object_id' => $group->id,
        //    'begin' => $begin,
        //    'end' => $end
        //]);

        foreach ($streams as $stream_id) {
            Privilege::create([
                'actor_seat_id' => $seat->id,
                'action' => 'beperkt opgavensets samenstellen',
                'object_type' => 'stream',
                'object_id' => $stream_id,
                'begin' => $begin,
                'end' => $end
            ]);
        }

        $leerlingen = 3;
        while ($leerlingen--) {
            $seat = Seat::create([
                'license_id' => $license->id,
                'role' => 'leerling'
            ]);
            //SeatGroup::create([
            //    'seat_id' => $seat->id,
            //    'group_id' => $group->id,
            //]);
            foreach ($streams as $stream_id) {
                Privilege::create([
                    'actor_seat_id' => $seat->id,
                    'action' => 'oefensets uitvoeren',
                    'object_type' => 'stream',
                    'object_id' => $stream_id,
                    'begin' => $begin,
                    'end' => $end
                ]);
            }
        }

        return $license;
    }
}
