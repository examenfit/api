<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeerlingInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $seat;
    public $link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($seat)
    {
        $app_url = config('app.dashboard_url');
        $token = $seat->token;
        $this->link = "{$app_url}/leerlinguitnodiging-bevestigen/{$token}"; // vak/niveau?
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
                    ->view('mail.leerling-invite');
    }
}
