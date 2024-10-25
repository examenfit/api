<?php

namespace App\Http\Requests\Auth;

use App\Models\Group;
use App\Models\License;
use App\Models\Privilege;
use App\Models\Seat;
use App\Models\Stream;
use App\Models\User;

use DateTime;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use Jumbojett\OpenIDConnectClient;

class BoomAuthRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'token' => 'required|string',
        ];
    }


    private function getOpenIDClient()
    {
        $oidc = new OpenIDConnectClient(
          config('boom.oidc.provider'),
          config('boom.oidc.client.id'),
          config('boom.oidc.client.secret')
        );

        return $oidc;
    }

    private function requestUserInfo()
    {
        $token = $this->input('token');
// Log::info('token='.$token);

        $oidc = $this->getOpenIDClient();
        $oidc->addScope(['licenties', 'BRIN-openid']);
        $oidc->setAccessToken($token);

        $userInfo = $oidc->requestUserInfo();
// Log::info('userInfo='.json_encode($userInfo, JSON_PRETTY_PRINT));

        $this->validateUserInfo($userInfo);

        return $userInfo;
    }
    
    private function validateUserInfo($userInfo)
    {
        $REQUIRED_PROPERTIES = [
          'email',
          'brin_id',
          'licenses',
        ];

        $messages = [];
        $hasError = false;

        foreach ($REQUIRED_PROPERTIES as $property) {

            if (!property_exists($userInfo, $property)) {
Log::info('Invalid userInfo; missing property='.$property);
                $hasError = true;
                $messages[$property] = __('Required');
            }
        }

        if ($hasError) {
            $this->triggerRateLimit();
            throw ValidationException::withMessages($messages);
        }

        $this->clearRateLimit();
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $data = $this->requestUserInfo();


        // collect role, until & streams
        $role = 'leerling';
        $privileges = [];
        $until = new DateTime('2025-08-01');

        $valid = FALSE;
        $licenses = json_decode($data->licenses);
        $LICENSES = config('boom.licenses');

        foreach ($LICENSES as $EAN => $options) {
          if (in_array($EAN, $licenses)) {
            $until = '2025-08-01';
            foreach($options as $option => $value) {
              if ($option === 'role') {
                $role = $value;
              }
              else if ($option === 'until') {
                $until = $value;
              }
              else {
                $privileges[$value] = [
                  'EAN' => $EAN,
                  'until' => $until,
                ];
                $valid = TRUE;
              }
            }
          }
        }

        if (!$valid) {
Log::info('No valid license(s) found');
          $this->triggerRateLimit();
          throw ValidationException::withMessages([
            'licenses' => __('No valid license(s) found')
          ]);
        }

        $user = User::firstOrCreate([
          'email' => $data->email,
        ], [
          'role' => $role,
          'first_name' => $data->given_name,
          'last_name' => $data->family_name,
          'password' => Str::random(6),
        ]);

// Log::info('user='.json_encode($user, JSON_PRETTY_PRINT));

// 
// 
DB::transaction(function() use ($data, $privileges, $role, $until, $user) {
// 
// 
        $license = License::firstOrCreate([
          'brin_id' => $data->brin_id
        ], [
          'type' => 'boom',
          'begin' => new DateTime(),
          'end' => $until,
          'description' => 'Boom, '.$data->brin_id,
          'slug' => strtolower($data->brin_id)
        ]);

        if ($license->end < $until) {
          $license->end = $until;
          $license->save();
        }

// Log::info('license='.json_encode($license, JSON_PRETTY_PRINT));

        $seat = Seat::firstOrCreate([
          'user_id' => $user->id,
          'license_id' => $license->id,
        ], [
          'role' => $role,
        ]);

// Log::info('seat='.json_encode($seat, JSON_PRETTY_PRINT));

        foreach ($privileges as $slug => $privilege) {
          $EAN = $privilege['EAN'];
          $until = $privilege['until'];
          $stream = Stream::firstWhere('slug', $slug);
          $stream_name = $stream->course->name . ' ' . $stream->level->name;
// Log::info('stream_name='.$stream_name);

          if ($stream->level->name === 'Vmbo GT') $grades = [3, 4];
          if ($stream->level->name === 'Havo') $grades = [4, 5];
          if ($stream->level->name === 'Vwo') $grades = [5, 6];

          foreach ($grades as $grade) {
            $group = Group::firstOrCreate([
              'license_id' => $license->id,
              'stream_id' => $stream->id,
              'brin_id' => $data->brin_id,
              'name' => "$stream_name $grade",
            ], [
              'is_active' => TRUE,
            ]);
          }

// Log::info('group='.json_encode($group, JSON_PRETTY_PRINT));

          if ($role === 'leerling') {
            $oefensets_uitvoeren = Privilege::firstOrCreate([
              'actor_seat_id' => $seat->id,
              'action' => 'oefensets uitvoeren',
              'object_type' => 'stream',
              'object_id' => $stream->id,
              'ean' => $EAN,
            ], [
              'begin' => new DateTime(),
              'end' => $license->end,
            ]);
                
            if ($oefensets_uitvoeren->end < $until) {
              $oefensets_uitvoeren->end = $until;
              $oefensets_uitvoeren->save();
            }

// Log::info('privilege='.json_encode($oefensets_uitvoeren, JSON_PRETTY_PRINT));
          }

          if ($role === 'docent') {

            $opgavensets_samenstellen = Privilege::firstOrCreate([
              'actor_seat_id' => $seat->id,
              'action' => 'opgavensets samenstellen',
              'object_type' => 'stream',
              'object_id' => $stream->id,
              'ean' => $EAN,
            ], [
              'begin' => new DateTime(),
              'end' => $license->end,
            ]);
                
            if ($opgavensets_samenstellen->end < $until) {
              $opgavensets_samenstellen->end = $until;
              $opgavensets_samenstellen->save();
            }

// Log::info('privilege='.json_encode($opgavensets_samenstellen, JSON_PRETTY_PRINT));
/*
            $groepen_beheren = Privilege::firstOrCreate([
              'actor_seat_id' => $seat->id,
              'action' => 'groepen beheren',
              'object_type' => 'group',
              'object_id' => $group->id,
            ], [
              'begin' => new DateTime(),
              'end' => $license->end,
            ]);

            if ($groepen_beheren->end < $until) {
              $groepen_beheren->end = $until;
              $groepen_beheren->save();
            }
// Log::info('privilege='.json_encode($groepen_beheren, JSON_PRETTY_PRINT));
*/

          }

        }

//
// 
}); /* transaction */
// 
//

Log:info("Auth::login {$user->email}");
        Auth::login($user);

        return $user;
    }

    private function ensureIsNotRateLimited()
    {
        if ($this->isRateLimited()) {
          event(new Lockout($this));
          throw ValidationException::withMessages([
            'token' => trans('auth.throttle', [
              'availableIn' => RateLimiter::availableIn($this->throttleKey()),
            ]),
          ]);
        }
    }
    
    private function triggerRateLimit()
    {
        RateLimiter::hit($this->throttleKey());
    }

    private function isRateLimited()
    {
        return RateLimiter::tooManyAttempts($this->throttleKey(), 5);
    }

    private function clearRateLimit()
    {
        RateLimiter::clear($this->throttleKey());
    }

    private function throttleKey()
    {
        return $this->ip();
    }

}
