<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
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

    private function introspectToken($token)
    {
Log::info($token);
        $oidc = $this->getOpenIDClient();
        $oidc->addScope(['licenties', 'BRIN-openid']);
        $oidc->setAccessToken($token);
        $data = $oidc->requestUserInfo();
Log::info(json_encode($data, JSON_PRETTY_PRINT));
        return $data;
    }
    
    private function validateData($data)
    {
        $isValid = property_exists($data, 'email');
        if ($isValid) {
          $this->clearRateLimit();
        }
        else {
          $this->triggerRateLimit();
          throw ValidationException::withMessages([
            'token' => __('Cannot resolve email'),
          ]);
        }
    }

    private function getTokenData()
    {
        $token = $this->input('token');
        $data = $this->introspectToken($token);

        $this->validateData($data);

        return $data;
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $data = $this->getTokenData();

        $user = User::query()
          ->where('email', $data->email)
          ->first();

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
