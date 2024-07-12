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
          config('boom.oidc.provider'),
          config('boom.oidc.client.id'),
          config('boom.oidc.client.secret')
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


        // collect role, until & streams
        $role = 'leerling';
        $streams = [];
        $until = new DateTime('2025-08-01');

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
                $streams[$value] = $until;
              }
            }
          }
        }

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
          'end' => $until,
          'description' => 'Boom, '.$data->brin_id
        ]);

        if ($license->end < $until) {
          $license->end = $until;
          $license->save();
        }

Log::info('license='.json_encode($license, JSON_PRETTY_PRINT));

        $seat = Seat::firstOrCreate([
          'user_id' => $user->id,
          'license_id' => $license->id,
        ], [
          'role' => 'leerling',
        ]);

Log::info('seat='.json_encode($seat, JSON_PRETTY_PRINT));

        foreach ($streams as $slug => $until) {
          $stream = Stream::firstWhere('slug', $slug);
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
                
            if ($oefensets_uitvoeren->end < $until) {
              $oefensets_uitvoeren->end = $until;
              $oefensets_uitvoeren->save();
            }

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
                
            if ($opgavensets_samenstellen->end < $until) {
              $opgavensets_samenstellen->end = $until;
              $opgavensets_samenstellen->save();
            }

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

            if ($groepen_beheren->end < $until) {
              $groepen_beheren->end = $until;
              $groepen_beheren->save();
            }

Log::info('privilege='.json_encode($groepen_beheren, JSON_PRETTY_PRINT));

            $seat->role = 'docent';
            $seat->save();

            $user->role = 'docent';
            $user->save();

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
