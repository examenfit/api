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
          config('boom.oidc_provider'),
          config('boom.oidc_client_id'),
          config('boom.oidc_client_secret')
        );

        return $oidc;
    }

    private function requestUserInfo()
    {
        $token = $this->input('token');
Log::info('token='.$token);

        $oidc = $this->getOpenIDClient();
        $oidc->addScope(['licenties', 'BRIN-openid']);
        $oidc->setAccessToken($token);

        $data = $oidc->requestUserInfo();
Log::info('userInfo='.json_encode($data, JSON_PRETTY_PRINT));

        $this->validateUserInfo($data);

        return $data;
    }
    
    private function validateUserInfo($data)
    {
        $hasRequiredProperties =
          property_exists($data, 'email') &&
          property_exists($data, 'brin_id') &&
          property_exists($data, 'licenses');

        $isValidUserInfo = 
          $hasRequiredProperties;

        if ($isValidUserInfo) {
          $this->clearRateLimit();
        }
        else {
          $this->triggerRateLimit();
          throw ValidationException::withMessages([
            'token' => __('Cannot resolve email'),
          ]);
        }
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $data = $this->requestUserInfo();

        $user = User::firstOrCreate([
          'email' => $data->email,
        ], [
          'password' => Str::random(6),
          'first_name' => $data->given_name,
          'last_name' => $data->family_name,
          'role' => 'leerling',
        ]);

Log::info('user='.json_encode($user, JSON_PRETTY_PRINT));

        $license = License::firstOrCreate([
          'brin_id' => $data->brin_id
        ], [
          'type' => 'boom',
          'begin' => new DateTime(),
          'end' => new DateTime('2025-08-01'),
          'description' => 'Boom, '.$data->brin_id
        ]);

Log::info('license='.json_encode($license, JSON_PRETTY_PRINT));

        $seat = Seat::firstOrCreate([
          'user_id' => $user->id,
          'license_id' => $license->id,
        ], [
          'role' => 'leerling',
        ]);

Log::info('seat='.json_encode($seat, JSON_PRETTY_PRINT));

        $eans = json_decode($data->licenses);

        $LICENSES = config('boom.licenses');
        // is docent
        // is leerling

        foreach ($eans as $ean) {
          if (array_key_exists($ean, $LICENSES)) {;
            foreach($LICENSES[$ean] as $privilege) {

              $role = $privilege['role'];
              $stream = Stream::firstWhere('id', $privilege['stream']);

              $stream_name = $stream->course->name . ' ' . $stream->level->name;
Log::info('stream_name='.$stream_name);

              $group = Group::firstOrCreate([
                'license_id' => $license->id,
                'stream_id' => $stream->id,
                'name' => $stream_name,
              ], [
                'is_active' => TRUE,
              ]);

Log::info('group='.json_encode($group, JSON_PRETTY_PRINT));

              if ($role === 'leerling') {

                $seat_in_group = $group->seats()->firstWhere(['seat_id' => $seat->id]);
                if (!$seat_in_group) {
Log::info('attach seat_id='.$seat->id);
                  $group->seats()->attach([ $seat->id ]);
                }

                $oefensets_uitvoeren = Privilege::firstOrCreate([
                  'actor_seat_id' => $seat->id,
                  'action' => 'oefensets uitvoeren',
                  'object_type' => 'stream',
                  'object_id' => $stream->id,
                ], [
                  'begin' => new DateTime(),
                  'end' => $license->end,
                ]);
                
Log::info('privilege='.json_encode($oefensets_uitvoeren, JSON_PRETTY_PRINT));
              }

              if ($role === 'docent') {

                $opgavensets_samenstellen = Privilege::firstOrCreate([
                  'actor_seat_id' => $seat->id,
                  'action' => 'opgavensets samenstellen',
                  'object_type' => 'stream',
                  'object_id' => $stream->id,
                ], [
                  'begin' => new DateTime(),
                  'end' => $license->end,
                ]);
                
Log::info('privilege='.json_encode($opgavensets_samenstellen, JSON_PRETTY_PRINT));

                $groepen_beheren = Privilege::firstOrCreate([
                  'actor_seat_id' => $seat->id,
                  'action' => 'groepen beheren',
                  'object_type' => 'group',
                  'object_id' => $group->id,
                ], [
                  'begin' => new DateTime(),
                  'end' => $license->end,
                ]);

Log::info('privilege='.json_encode($groepen_beheren, JSON_PRETTY_PRINT));

                $seat->role = 'docent';
                $seat->save();

                $user->role = 'docent';
                $user->save();

              }

              // add to group
            }
          }
        }

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
