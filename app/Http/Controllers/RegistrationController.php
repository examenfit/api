<?php

namespace App\Http\Controllers;

use Exception;

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

    public function store(RegistrationRequest $request)
    {
        $data = $request->validated();
        try
        {
            throw new \ErrorException('test error handling');

            $registration = Registration::create($data);
            $registration->save();
            return view('registration.success', $registration);
        }
        catch (Exception $error)
        {
            return view('registration.failure', [ 'message' => $error->getMessage() ]);
        }
    }

}
