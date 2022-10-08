<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;
    public $link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $token)
    {
        $app_url = config('app.dashboard_url');
        $this->user = $user;
        $this->token = $token;
        $this->link = "{$app_url}/reset-password#{$token}";
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@examenfit.nl')
                    ->bcc('examenfit@hotmail.com', 'Examenfit')
                    ->subject('Wachtwoord opnieuw instellen')
                    ->view('mail.password-reset');
    }
}
