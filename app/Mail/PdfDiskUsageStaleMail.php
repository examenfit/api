<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Mail\Mailable;

class PdfDiskUsageStaleMail extends Mailable
{
    public function __construct(public ?Carbon $lastMeasuredAt)
    {
    }

    public function build()
    {
        return $this->subject('PDF-server: geen schijfmeting meer ontvangen')
                    ->view('mail.pdf-disk-usage-stale');
    }
}
