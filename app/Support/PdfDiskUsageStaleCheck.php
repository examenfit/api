<?php

namespace App\Support;

use App\Mail\PdfDiskUsageStaleMail;
use App\Models\PdfDiskUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Notices when the pdf server has stopped reporting: a silent cron job would also silence the full-disk mail.
class PdfDiskUsageStaleCheck
{
    private const THROTTLE_KEY = 'pdf-disk-usage-stale-alert';

    // Returns true when the measurements are stale.
    public function run(): bool
    {
        $lastMeasuredAt = PdfDiskUsage::max('created_at');
        $lastMeasuredAt = $lastMeasuredAt ? Carbon::parse($lastMeasuredAt) : null;

        if (!$this->isStale($lastMeasuredAt)) {
            return false;
        }

        $this->sendAlert($lastMeasuredAt);

        return true;
    }

    // Only production sets the setting: staging shares the pdf server, which reports to production only.
    private function isEnabled(): bool
    {
        return $this->staleAfterHours() > 0;
    }

    private function isStale(?Carbon $lastMeasuredAt): bool
    {
        $isOverdue = $lastMeasuredAt === null || $lastMeasuredAt->lt(now()->subHours($this->staleAfterHours()));

        return $this->isEnabled() && $isOverdue;
    }

    private function staleAfterHours(): int
    {
        return (int) config('pdf_generator.disk_usage_stale_after_hours');
    }

    private function sendAlert(?Carbon $lastMeasuredAt): void
    {
        $addresses = PdfAlertRecipients::addresses();

        if ($addresses === [] || !$this->claimInterval()) {
            return;
        }

        try {
            Mail::to($addresses)->send(new PdfDiskUsageStaleMail($lastMeasuredAt));
        } catch (Throwable $error) {
            // the exit code still tells Forge; the next run may try the mail again
            report($error);
            Cache::forget(self::THROTTLE_KEY);
        }
    }

    // Cache::add is atomic and needs a cache shared between php processes (file or redis, not array).
    private function claimInterval(): bool
    {
        $hours = config('pdf_generator.disk_usage_alert_interval_hours');

        return Cache::add(self::THROTTLE_KEY, true, now()->addHours($hours));
    }
}
