<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One measurement of the disk of the pdf generator server, posted by that server.
class PdfDiskUsage extends Model
{
    protected $fillable = ['percentage', 'used_mb', 'available_mb', 'alerted_at'];

    protected $casts = ['alerted_at' => 'datetime'];

    public function isCritical(): bool
    {
        return $this->percentage >= config('pdf_generator.disk_usage_critical_percentage');
    }
}
