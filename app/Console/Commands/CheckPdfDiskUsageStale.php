<?php

namespace App\Console\Commands;

use App\Support\PdfDiskUsageStaleCheck;
use Illuminate\Console\Command;

class CheckPdfDiskUsageStale extends Command
{
    protected $signature = 'pdf:check-stale';

    protected $description = 'Mail the owners when the pdf server has stopped reporting its disk usage (exit code 1 when stale)';

    public function handle(PdfDiskUsageStaleCheck $check): int
    {
        $isStale = $check->run();

        $this->line($isStale ? 'stale: no recent disk usage measurement' : 'ok (or switched off)');

        return $isStale ? self::FAILURE : self::SUCCESS;
    }
}
