<?php

namespace App\Support;

use App\Models\PdfDiskUsage;

// Thins out the hourly measurements: one a day after two days, one a week (Monday) after three weeks.
// Why 2 days: the critical mail reads alerted_at of the last 24 hours (PDF_DISK_USAGE_ALERT_INTERVAL_HOURS).
class PdfDiskUsagePruner
{
    private const KEEP_ALL_DAYS = 2;
    private const KEEP_DAILY_DAYS = 21;

    public function prune(): void
    {
        $this->deleteOlderThan(self::KEEP_ALL_DAYS, 'HOUR(created_at) <> 0');
        $this->deleteOlderThan(self::KEEP_DAILY_DAYS, 'WEEKDAY(created_at) <> 0');
    }

    private function deleteOlderThan(int $days, string $condition): void
    {
        PdfDiskUsage::where('created_at', '<', now()->subDays($days))->whereRaw($condition)->delete();
    }
}
