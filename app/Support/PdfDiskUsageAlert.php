<?php

namespace App\Support;

use App\Mail\PdfDiskUsageMail;
use App\Models\PdfDiskUsage;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Mails the owners when the disk of the pdf server is critical, but not on every measurement.
class PdfDiskUsageAlert
{
    public function sendIfNeeded(PdfDiskUsage $usage): bool
    {
        if (!$this->isAlertDue($usage)) {
            return false;
        }

        try {
            Mail::to($this->recipients())->send(new PdfDiskUsageMail($usage));
        } catch (Throwable $error) {
            // the measurement is stored; a broken mail server must not make the pdf server's report fail
            report($error);
            return false;
        }

        $usage->update(['alerted_at' => now()]);

        return true;
    }

    private function isAlertDue(PdfDiskUsage $usage): bool
    {
        return $usage->isCritical() && $this->recipients() !== [] && !$this->wasAlertedRecently();
    }

    private function wasAlertedRecently(): bool
    {
        $hours = config('pdf_generator.disk_usage_alert_interval_hours');

        return PdfDiskUsage::where('alerted_at', '>', now()->subHours($hours))->exists();
    }

    private function recipients(): array
    {
        $addresses = explode(',', (string) config('pdf_generator.disk_usage_alert_to'));

        return array_values(array_filter(array_map('trim', $addresses)));
    }
}
