<?php

namespace Tests\Feature;

use App\Models\PdfDiskUsage;
use App\Support\PdfDiskUsagePruner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDiskUsagePrunerTest extends TestCase
{
    use RefreshDatabase;

    // 2026-11-07 is a Saturday; 2026-11-02 and 2026-10-12 are Mondays.
    private const NOW = '2026-11-07 12:30:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(self::NOW);
    }

    private function measureAt(string $moment): PdfDiskUsage
    {
        $usage = PdfDiskUsage::create(['percentage' => 50]);
        $usage->forceFill(['created_at' => $moment])->save();

        return $usage;
    }

    private function remainingMoments(): array
    {
        return PdfDiskUsage::orderBy('created_at')->pluck('created_at')
            ->map(fn ($moment) => $moment->format('Y-m-d H:i'))->all();
    }

    public function test_measurements_of_the_last_two_days_all_stay(): void
    {
        $this->measureAt('2026-11-06 13:05:00');
        $this->measureAt('2026-11-05 14:05:00');

        (new PdfDiskUsagePruner())->prune();

        $this->assertCount(2, PdfDiskUsage::all());
    }

    public function test_older_than_two_days_only_the_midnight_measurement_stays(): void
    {
        $this->measureAt('2026-11-04 00:05:00');
        $this->measureAt('2026-11-04 01:05:00');
        $this->measureAt('2026-11-04 23:05:00');

        (new PdfDiskUsagePruner())->prune();

        $this->assertSame(['2026-11-04 00:05'], $this->remainingMoments());
    }

    public function test_older_than_three_weeks_only_the_monday_midnight_measurement_stays(): void
    {
        $this->measureAt('2026-10-12 00:05:00');
        $this->measureAt('2026-10-13 00:05:00');
        $this->measureAt('2026-10-12 05:05:00');

        (new PdfDiskUsagePruner())->prune();

        $this->assertSame(['2026-10-12 00:05'], $this->remainingMoments());
    }

    public function test_a_post_prunes_old_measurements(): void
    {
        config(['pdf_generator.disk_usage_token' => 'secret-token']);
        $this->measureAt('2026-11-04 01:05:00');

        $this->postJson('/api/pdf-generator/disk-usage', ['percentage' => 40], ['X-Disk-Usage-Token' => 'secret-token'])
            ->assertCreated();

        $this->assertCount(1, PdfDiskUsage::all());
    }

    public function test_a_post_still_succeeds_when_the_pruning_fails(): void
    {
        config(['pdf_generator.disk_usage_token' => 'secret-token']);
        $this->mock(PdfDiskUsagePruner::class)->shouldReceive('prune')->andThrow(new \RuntimeException('db gone'));

        $this->postJson('/api/pdf-generator/disk-usage', ['percentage' => 40], ['X-Disk-Usage-Token' => 'secret-token'])
            ->assertCreated();

        $this->assertCount(1, PdfDiskUsage::all());
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $this->measureAt('2026-11-04 00:05:00');
        $this->measureAt('2026-11-04 01:05:00');
        (new PdfDiskUsagePruner())->prune();

        (new PdfDiskUsagePruner())->prune();

        $this->assertSame(['2026-11-04 00:05'], $this->remainingMoments());
    }
}
