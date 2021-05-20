<?php namespace App\Http\Controllers;

use Mail;
use Exception;

use App\Mail\RegistrationMail;
use App\Models\Registration;
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

    private function createRegistration($data)
    {
        $data['activation_code'] = md5($data['email'].rand(0, 999999999));
        $registration = Registration::create($data);
        $registration->save();
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
