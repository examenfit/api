<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PdfServerFailureMail extends Mailable
{
    public function __construct(
        public string $command,
        public ?int $status,
        public string $output
    ) {
    }

    public function build()
    {
        return $this->subject('PDF-server: een document kon niet worden aangemaakt')
                    ->view('mail.pdf-server-failure');
    }
}
