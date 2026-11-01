<?php

namespace App\Mail;

use App\Models\PdfDiskUsage;
use Illuminate\Mail\Mailable;

class PdfDiskUsageMail extends Mailable
{
    public function __construct(public PdfDiskUsage $usage)
    {
    }

    public function build()
    {
        return $this->subject("PDF-server: schijf {$this->usage->percentage}% vol")
                    ->view('mail.pdf-disk-usage');
    }
}
