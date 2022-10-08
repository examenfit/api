<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactRequest;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($contactRequest)
    {
        $this->contactRequest = $contactRequest;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $id = $this->contactRequest->id;
        $name = $this->contactRequest->user->full_name;
        $email = $this->contactRequest->user->email;
        $subject = "Contact verzoek #$id: $name <$email>"; 
        return $this->from('info@examenfit.nl')
                    ->bcc('examenfit@hotmail.com', 'Examenfit')
                    ->subject($subject)
                    ->view('mail.contact-request');
    }
}
