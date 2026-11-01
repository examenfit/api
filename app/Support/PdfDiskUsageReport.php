<?php

namespace App\Support;

use App\Models\PdfDiskUsage;

// The rows of the admin overview (Admin -> Overzichten): the latest measurements, newest first.
class PdfDiskUsageReport
{
    private const LIMIT = 500;

    public function rows(): array
    {
        return PdfDiskUsage::orderByDesc('created_at')->orderByDesc('id')->limit(self::LIMIT)->get()
            ->map(fn (PdfDiskUsage $usage) => $this->row($usage))
            ->all();
    }

    private function row(PdfDiskUsage $usage): array
    {
        return [
            'Tijdstip' => $usage->created_at->format('Y-m-d H:i'),
            'Gebruikt (%)' => $usage->percentage,
            'Gebruikt (MB)' => $usage->used_mb,
            'Beschikbaar (MB)' => $usage->available_mb,
            'Status' => $usage->isCritical() ? 'kritiek' : 'ok',
            'Mail verstuurd' => $usage->alerted_at?->format('Y-m-d H:i'),
        ];
    }
}
