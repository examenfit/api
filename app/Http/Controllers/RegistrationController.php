<?php namespace App\Http\Controllers;

use DateTime;
use ErrorException;
use Exception;
use Mail;

use Illuminate\Support\Str;

use App\Mail\RegistrationMail;
use App\Mail\BulkRegistrationMail;
use App\Mail\BulkLeerlingRegistrationMail;
use App\Models\Registration;
use App\Models\License;
use App\Models\User;
use App\Http\Requests\RegistrationRequest;
use App\Http\Resources\RegistrationResource;

use Illuminate\Http\Request;

use Mollie\Api\MollieApiClient;

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

    private function sendBulkLeerlingRegistrationMail($registration)
    {
        $mail = new BulkLeerlingRegistrationMail($registration);
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

    public function processLeerling($data)
    {
        $registration = $this->createRegistration($data);
        $this->sendBulkLeerlingRegistrationMail($registration);
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

    function getMollieApiClient() {
        $mollie_api_key = config('app.mollie_api_key');

        $mollie = new MollieApiClient();
        $mollie->setApiKey($mollie_api_key);

        // some sort of validation?

        return $mollie;
    }

    function createPayment($description, $amount, $redirectUrl)
    {
        $api = config('app.url');
        $mollie = $this->getMollieApiClient();

        $payments = $mollie->payments;
        $payment = $payments->create([
            "amount" => [
                "currency" => "EUR",
                "value" => number_format($amount, 2, '.', '')
            ],
            "method" => null,
            "description" => $description,
            "redirectUrl" => $redirectUrl,
            //"webhookUrl"  => "$api/api/leerling-payment",
        ]);

        return $payment;
    }

    function getPaymentById($paymentId) {
        $mollie = $this->getMollieApiClient();
        $payments = $mollie->payments;
        $payment = $payments->get($paymentId);
        return $payment;
    }

    public function registerLeerling(RegistrationRequest $request)
    {
        $data = $request->validated();
        $leerlingData = $request->validate([
          'streams' => 'required|array',
          'streams.*' => 'required|string'
        ]);

        try
        {
            $data['stream_slugs'] = json_encode($leerlingData['streams']);
            $registration = $this->createRegistration($data);

            // forward to payment page
            $redirectUrl = $registration->getActivationUrl();
            $count = count($leerlingData['streams']);
            $description = "Leerlinglicentie, $count vak(ken)";
            $price = 17.5 * $count;
            $email = $registration->email;
            if ($count === 1) {
              $payment = $this->createPayment("Leerlinglicentie, 1 vak ($email)", 20.00, $redirectUrl);
            } else if ($count === 2) {
              $payment = $this->createPayment("Leerlinglicentie, 2 vakken ($email)", 35.00, $redirectUrl);
            } else {
              $payment = $this->createPayment("Leerlinglicentie, alle vakken ($email)", 40.00, $redirectUrl);
            }
            $registration->payment_id = $payment->id;
            $registration->payment_status = $payment->status;
            $registration->save();

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

    function refreshPayment($payment, $registration)
    {
        $description = $payment->description;
        $amount = $payment->amount->value;
        $redirectUrl = $registration->getActivationUrl();

        $payment = $this->createPayment($description, $amount, $redirectUrl);

        $registration->payment_id = $payment->id;
        $registration->save();

        return $payment;
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

        if ($registration->payment_id && $registration->payment_status !== 'paid') {
          $payment = $this->getPaymentById($registration->payment_id);

          $isCanceled = $payment->status === 'canceled';
          $isExpired = $payment->status === 'expired';
          $isFailed = $payment->status === 'failed';
          if ($isCanceled || $isExpired || $isFailed) {
            // create payment again
            $payment = $this->refreshPayment($payment, $registration);
          }

          $isOpen = $payment->status === 'open';
          if ($isOpen) {
            return response()->json([
              'info' => 'payment needed',
              'checkout_url' => $payment->getCheckoutUrl()
            ]);
          }

          $isPaid = $payment->status === 'paid';
          if (!$isPaid) {
            return response()->json([
              'info' => 'payment status unknown',
              'payment' => $payment
            ]);
          }
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

    const PROEFLICENTIES = [
      'proeflicentie' => 'proeflicentie',
      'proeflicentie-natuurkunde' => 'proeflicentie natuurkunde havo / vwo',
      'proeflicentie-scheikunde' => 'proeflicentie scheikunde havo / vwo',
      'proeflicentie-wiskunde-a' => 'proeflicentie wiskunde A havo / vwo',
      'proeflicentie-wiskunde-b' => 'proeflicentie wiskunde B havo / vwo',
      'proeflicentie-wiskunde' => 'proeflicentie wiskunde vmbo GT',
      'proeflicentie-nask1' => 'proeflicentie nask1 vmbo GT',
      'proeflicentie-nask2' => 'proeflicentie nask2 vmbo GT',
    ];

    const STREAMS = [
      'proeflicentie' => [ 1, 2 ],
      'proeflicentie-natuurkunde' => [ 5, 6 ],
      'proeflicentie-scheikunde' => [ 8, 9 ],
      'proeflicentie-wiskunde-a' => [ 1, 2 ],
      'proeflicentie-wiskunde-b' => [ 3, 4 ],
      'proeflicentie-wiskunde' => [ 7 ],
      'proeflicentie-nask1' => [ 10 ],
      'proeflicentie-nask2' => [ 11 ],
    ];

    private function activateProeflicentie($user, $registration)
    {
        $user->role = 'docent';
        $user->newsletter = $registration->newsletter ?: 0;
        $user->save();

        $descr = RegistrationController::PROEFLICENTIES[$registration->license];
        $streams = RegistrationController::STREAMS[$registration->license];

        License::createProeflicentie($user, $streams, $descr);

        $registration->activated = new DateTime();
        $registration->save();
    }

    const STREAM_SLUGS = [
      'wiskunde-a-havo' => 1,
      'wiskunde-a-vwo' => 2,
      'wiskunde-b-havo' => 3,
      'wiskunde-b-vwo' => 4,
      'natuurkunde-havo' => 5,
      'natuurkunde-vwo' => 6,
      'wiskunde-vmbo' => 7,
      'scheikunde-havo' => 8,
      'scheikunde-vwo' => 9,
      'nask1-vmbo' => 10,
      'nask2-vmbo' => 11
    ];

    private function mapStreams($stream_slugs)
    {
        $mapping = RegistrationController::STREAM_SLUGS;
        return array_map(fn($slug) => $mapping[$slug], $stream_slugs);
    }

    private function activateLeerlinglicentie($user, $registration)
    {
        $user->role = 'leerling';
        $user->newsletter = $registration->newsletter ?: 0;
        $user->save();

        $stream_slugs = json_decode($registration->stream_slugs);
        try {
          $descr = $registration->license. ' ' . implode('+', $stream_slugs);
        } catch (Exception $err) {
          $descr =  $registration->license. ' ' . $registration->stream_slugs;
        }
        $streams = $this->mapStreams($stream_slugs);

        License::createLeerlinglicentie($user, $streams, $descr);

        $registration->activated = new DateTime();
        $registration->save();
    }

    function isValidProeflicentie($license)
    {
      return array_key_exists($license, RegistrationController::PROEFLICENTIES);
    }

    function isLeerlinglicentie($license)
    {
      return str_starts_with($license, 'leerlinglicentie');
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
            if ($this->isValidProeflicentie($registration->license)) {
                $this->activateProeflicentie($user, $registration);
                return $registration;
            }
            if ($this->isLeerlinglicentie($registration->license)) {
                $this->activateLeerlinglicentie($user, $registration);
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

    // admin

    public function all()
    {
        return RegistrationResource::collection(Registration::all());
    }

    public function get(Registration $registration)
    {
        return new RegistrationResource($registration);
    }
}
