<?php

namespace App\Support;

use App\Mail\PdfServerFailureMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Mails the owners when a script on the pdf server fails, but not for every click of every user during an outage.
class RemoteScriptFailureAlert
{
    private const THROTTLE_KEY = 'pdf-server-failure-alert';

    public function send(string $command, ?int $status, string $output): bool
    {
        $addresses = PdfAlertRecipients::addresses();

        if ($addresses === [] || !$this->claimInterval()) {
            return false;
        }

        try {
            Mail::to($addresses)->send(new PdfServerFailureMail($command, $status, $output));
        } catch (Throwable $error) {
            // the visitor gets the 502 either way, and the next failure may try again
            report($error);
            Cache::forget(self::THROTTLE_KEY);
            return false;
        }

        return true;
    }

    // Cache::add is atomic: of simultaneous failures only one gets the slot
    private function claimInterval(): bool
    {
        $hours = config('pdf_generator.failure_alert_interval_hours');

        return Cache::add(self::THROTTLE_KEY, true, now()->addHours($hours));
    }
}
