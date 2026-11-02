<?php

namespace App\Support;

// The owners who are mailed about the pdf server (full disk, scripts that fail). Comma separated in PDF_DISK_USAGE_ALERT_TO;
// empty means no mail, which is how staging stays quiet: staging and production share one pdf server.
class PdfAlertRecipients
{
    public static function addresses(): array
    {
        $addresses = explode(',', (string) config('pdf_generator.disk_usage_alert_to'));

        return array_values(array_filter(array_map('trim', $addresses)));
    }
}
