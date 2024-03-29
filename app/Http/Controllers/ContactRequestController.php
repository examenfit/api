<?php namespace App\Http\Controllers;

use ErrorException;
use Exception;
use Mail;

use App\Mail\ContactRequestMail;
use App\Models\ContactRequest;
use App\Http\Resources\ContactRequestResource;

use Illuminate\Http\Request;

class ContactRequestController extends Controller
{
    public function index()
    {
        $requests = ContactRequest::all();
        $requests->load([ 'user' ]);
        return ContactRequestResource::collection($requests);
    }

    private function sendMail($contactRequest)
    {
        $addr = config('app.contact_request_to');
        $mail = new ContactRequestMail($contactRequest);
        Mail::to($addr)->send($mail);
    }

    private function createContactRequest()
    {
        $user = auth()->user();
        if (!$user) {
          return response()->noContact(401);
        }
        return ContactRequest::create([
            'user_id' => $user->id
        ]);
    }

    public function store(Request $request)
    {
        $contactRequest = $this->createContactRequest();
        $this->sendMail($contactRequest);
        return response()->noContent(201);
    }

    public function get(ContactRequest $contactRequest)
    {
        return new ContactRequestResource($contactRequest);
    }

    public function put(ContactRequest $contactRequest)
    {
        $request->validate([ 
          'status' => 'boolean:required'
        ]);
        $contactRequest->status = $request->status;
        $contactRequest->save();

        return new ContactRequestResource($contactRequest);
    }
}
