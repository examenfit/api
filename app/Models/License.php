<?php

namespace App\Models;

use DateTime;
use DateInterval;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory, HashID;


    public $fillable = [
        'type',
        'begin',
        'end',
        'description'
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
        $end->add(new DateInterval('P10D'));

        $feb1 = new DateTime('2022-02-01');
        if ($end < $feb1) {
            $end = $feb1;
        }

        $license = License::create([
            'type' => 'proeflicentie',
            'begin' => $begin,
            'end' => $end,
            'description' => 'proeflicentie ' . $user->email
        ]);

        $seat = $license->seats()->create([
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

        $stream = Stream::firstWhere('id', $streams[0]);
        $course = $stream->course->name;
        $group = Group::create([
          'license_id' => $license->id,
          'name' => $course,
          'is_active' => 1
        ]);

        Privilege::create([
            'actor_seat_id' => $seat->id,
            'action' => 'groepen beheren',
            'object_type' => 'group',
            'object_id' => $group->id,
            'begin' => $begin,
            'end' => $end
        ]);

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

        $leerlingen = 4;
        while ($leerlingen--) {
            $seat = Seat::create([
                'license_id' => $license->id,
                'role' => 'leerling'
            ]);
            $seat->groups()->sync([ $group->id ]);
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


        $user->link = Str::random(32);
        $user->save();

        $leonie = User::create([
            'first_name' => 'Leonie',
            'last_name' => 'Eerling',
            'role' => 'leerling',
            'email' => 'leerling-'.Str::random(6).'@examenfit.nl',
            'password' => '',
            'link' => $user->link
        ]);

        // seat = first leerling added
        $seat = $license->seats[1];
        $seat->first_name = 'Demo leerling';
        $seat->user_id = $leonie->id;
        $seat->save();

        return $license;
    }

    public static function createDemoLeerling($license)
    {
        $docent = Seat::query()
          ->where('license_id', $license->id)
          ->where('role', 'docent')
          ->first();

        $seat = Seat::query()
          ->where('license_id', $license->id)
          ->where('role', 'leerling')
          ->first();

        $user = auth()->user();
        $leonie = User::create([
            'first_name' => 'Leonie',
            'last_name' => 'Eerling',
            'role' => 'leerling',
            'email' => 'leerling-'.Str::random(6).'@examenfit.nl',
            'password' => '',
            'link' => $user->link
        ]);

        $demo = Seat::create([
            'license_id' => $license->id,
            'role' => 'leerling',
            'first_name' => 'Demo leerling',
            'user_id' => $leonie->id,
        ]);

        $groups = Group::query()
          ->where('license_id', $license->id)
          ->get();

        $sync = [];
        foreach($groups as $group)
        {
            $sync[] = $group->id;
        }

        $demo->groups()->sync($sync);
        foreach($seat->privileges as $priv)
        {
            Privilege::create([
                'actor_seat_id' => $demo->id,
                'action' => $priv->action,
                'object_type' => $priv->object_type,
                'object_id' => $priv->object_id,
                'begin' => $license->begin,
                'end' => $license->end
            ]);
        }

        return $demo;
    }
}
