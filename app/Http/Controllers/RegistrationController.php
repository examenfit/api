<?php namespace App\Http\Controllers;

use DateTime;
use ErrorException;
use Exception;
use Mail;

use Illuminate\Support\Str;

use App\Mail\RegistrationMail;
use App\Mail\BulkRegistrationMail;
use App\Models\Registration;
use App\Models\License;
use App\Models\User;
use App\Http\Requests\RegistrationRequest;
use App\Http\Resources\RegistrationResource;

use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function form()
    {
        return view('registration.form');
    }

    public function index()
    {
        $registrations = Registration::all();
        return $registrations;
    }

    private function sendRegistrationMail($registration)
    {
        $mail = new RegistrationMail($registration);
        Mail::to($registration->email)->send($mail);
    }

    private function sendBulkRegistrationMail($registration)
    {
        $mail = new BulkRegistrationMail($registration);
        Mail::to($registration->email)->send($mail);
    }

    private function createRegistration($data)
    {
        $data['activation_code'] = Str::random(32);
        if (!array_key_exists('newsletter', $data)) {
          $data['newsletter'] = 0;
        }
        $registration = Registration::create($data);
        $registration->save();
        return $registration;
    }

    public function process($data)
    {
        $registration = $this->createRegistration($data);
        $this->sendBulkRegistrationMail($registration);
        return $registration;
    }

    public function store(RegistrationRequest $request)
    {
        $data = $request->validated();
        try
        {
            $registration = $this->createRegistration($data);
            $this->sendRegistrationMail($registration);
            return view('registration.success', $registration);
        }
        catch (Exception $error)
        {
            return view('registration.failure', [ 'message' => $error->getMessage() ]);
        }
    }

    public function register(RegistrationRequest $request)
    {
        $data = $request->validated();
        try
        {
            $registration = $this->createRegistration($data);
            $this->sendRegistrationMail($registration);
            return response()->json([ 'status' => 'success' ]);
        }
        catch (Exception $error)
        {
            return response()->json([ 'status' => 'failed', 'message' => $error->getMessage() ]);
        }
    }

    private function getRegistration(Request $request)
    {
        $activation_code = $request->activation_code;
        if ($activation_code) {
            $registration = Registration::where('activation_code', $activation_code)->first();
            return $registration;
        }
    }

    private function lookupUser(Registration $registration)
    {
        $user = User::where('email', $registration->email)->first();
        return $user;
    }

    public function activationStatus(Request $request)
    {
        $registration = $this->getRegistration($request);
        if (!$registration) {
            return response()->json(['info' => 'registration not found']);
        }
        if ($registration->activated) {
            return response()->json(['info' => 'registration activated already', 'activated' => $registration->activated ]);
        }
        $user = $this->lookupUser($registration);
        if ($user) {
            return response()->json(['info' => 'user exists']);
        }
        return response()->json(['info' => 'registration exists']);
    }

    public function activateAccount(Request $request)
    {
        try {
            $password = $request->password;
            if (!$password) {
                return response()->json(['message' => 'password required'], 400);
            }
            $registration = $this->getRegistration($request);
            if (!$registration) {
                return response()->json(['message' => 'activation_code invalid'], 406);
            }
            $now = new DateTime();
            $user = User::create([
                'first_name' => $registration->first_name,
                'last_name' => $registration->last_name,
                'email' => $registration->email,
                'password' => bcrypt($password),
                'email_verified_at' => $now, // fixme
                'role' => ''
            ]);
            $user->save();
            return $registration;
        } catch (Exception $err) {
            return response()->json(['message' => $err->getMessage()], 500);
        }
    }

    private function activateProeflicentie($user, $registration)
    {
        $user->role = 'docent';
        $user->newsletter = $registration->newsletter ?: 0;
        $user->save();

        License::createProeflicentie($user);

        $registration->activated = new DateTime();
        $registration->save();
    }

    public function activateLicense(Request $request)
    {
        try {
            $registration = $this->getRegistration($request);
            if (!$registration) {
                return response()->json(['message' => 'activation_code invalid'], 406);
            }
            $user = $this->lookupUser($registration);
            if (!$user) {
                return response()->json(['message' => 'user does not exist'], 406);
            }
            if ($registration->license === 'trial') {
                // "trial" is deprecated
                $this->activateProeflicentie($user, $registration);
                return $registration;
            }
            if ($registration->license === 'proeflicentie') {
                $this->activateProeflicentie($user, $registration);
                return $registration;
            }
            return response()->json(['message' => 'license invalid'], 406);
        } catch (Exception $err) {
            return response()->json(['message' => $err->getMessage()], 500);
        }
    }

    public function mail()
    {
        $recipient = 'stekelenburg@gmail.com';
        $registration = [
            'first_name' => 'Giel',
            'last_name' => 'Stekelenburg',
            'activation_code' => '0123456789abcdef0123456789abcdef'
        ];
        $this->sendRegistrationMail($recipient, $registration);
        return 'ok';
    }

}
