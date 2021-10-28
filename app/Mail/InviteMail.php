<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $seat;
    public $link;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($seat, $user)
    {
        $this->seat = $seat;
        $this->link = $this->activationLink();
        $this->user = $user;
    }

    private function activationLink()
    {
        $app_url = config('app.dashboard_url');
        $path = 'uitnodiging-accepteren';
        $token = $this->seat->token;
        $this->link = "{$app_url}/{$path}/{$token}"; // vak/niveau?
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@examenfit.nl')
                    ->subject('Aanmelding ExamenFit')
                    ->view('mail.invite');
    }
}
