<?php

namespace App\Http\Controllers;

use App\Models\PdfDiskUsage;
use App\Support\PdfDiskUsageAlert;
use App\Support\PdfDiskUsagePruner;
use Illuminate\Http\Request;

// Receives the disk usage that the pdf server posts from cron (scripts/pdf-server/report-disk-usage.sh).
class PdfDiskUsageController extends Controller
{
    public function store(Request $request, PdfDiskUsageAlert $alert, PdfDiskUsagePruner $pruner)
    {
        abort_unless($this->hasValidToken($request), 403);

        $usage = PdfDiskUsage::create($request->validate([
            'percentage' => 'required|integer|between:0,100',
            'used_mb' => 'nullable|integer|min:0',
            'available_mb' => 'nullable|integer|min:0',
        ]));
        $alert->sendIfNeeded($usage);
        $this->pruneOldMeasurements($pruner);

        return response()->json(['id' => $usage->id, 'critical' => $usage->isCritical()], 201);
    }

    // The measurement is already stored: a failing cleanup must not make the pdf server think the post failed.
    private function pruneOldMeasurements(PdfDiskUsagePruner $pruner): void
    {
        try {
            $pruner->prune();
        } catch (\Throwable $error) {
            report($error);
        }
    }

    private function hasValidToken(Request $request): bool
    {
        $expected = (string) config('pdf_generator.disk_usage_token');

        return $expected !== '' && hash_equals($expected, (string) $request->header('X-Disk-Usage-Token'));
    }
}
