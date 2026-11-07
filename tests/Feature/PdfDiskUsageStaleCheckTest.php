<?php

namespace Tests\Feature;

use App\Mail\PdfDiskUsageStaleMail;
use App\Models\PdfDiskUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PdfDiskUsageStaleCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pdf_generator.disk_usage_stale_after_hours' => 3,
            'pdf_generator.disk_usage_alert_to' => 'owner@example.com, second@example.com',
            'pdf_generator.disk_usage_alert_interval_hours' => 24,
        ]);
        Cache::flush();
        Mail::fake();
    }

    private function measureHoursAgo(int $hours): void
    {
        $usage = PdfDiskUsage::create(['percentage' => 40]);
        $usage->forceFill(['created_at' => now()->subHours($hours)])->save();
    }

    public function test_it_does_nothing_while_the_setting_is_off(): void
    {
        config(['pdf_generator.disk_usage_stale_after_hours' => 0]);

        $this->artisan('pdf:check-stale')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_a_fresh_measurement_is_fine(): void
    {
        $this->measureHoursAgo(1);

        $this->artisan('pdf:check-stale')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_a_stale_measurement_mails_the_owners_once_and_fails(): void
    {
        $this->measureHoursAgo(5);

        $this->artisan('pdf:check-stale')->assertExitCode(1);

        Mail::assertSent(PdfDiskUsageStaleMail::class, fn ($mail) => $mail->hasTo('owner@example.com')
            && $mail->hasTo('second@example.com')
            && str_contains($mail->render(), 'PDF_DISK_USAGE_TOKEN'));
    }

    public function test_a_second_run_within_the_interval_sends_no_second_mail(): void
    {
        $this->measureHoursAgo(5);

        $this->artisan('pdf:check-stale')->assertExitCode(1);
        $this->artisan('pdf:check-stale')->assertExitCode(1);

        Mail::assertSent(PdfDiskUsageStaleMail::class, 1);
    }

    public function test_after_the_interval_the_next_run_mails_again(): void
    {
        $this->measureHoursAgo(5);
        $this->artisan('pdf:check-stale');

        $this->travel(25)->hours();
        $this->artisan('pdf:check-stale');

        Mail::assertSent(PdfDiskUsageStaleMail::class, 2);
    }

    public function test_no_measurements_at_all_is_stale(): void
    {
        $this->artisan('pdf:check-stale')->assertExitCode(1);

        Mail::assertSent(PdfDiskUsageStaleMail::class, fn ($mail) => str_contains($mail->render(), 'nog nooit'));
    }

    public function test_without_addresses_nothing_is_sent_but_it_still_fails(): void
    {
        config(['pdf_generator.disk_usage_alert_to' => '']);

        $this->artisan('pdf:check-stale')->assertExitCode(1);

        Mail::assertNothingSent();
    }

    public function test_a_broken_mail_server_does_not_throw_and_does_not_use_up_the_interval(): void
    {
        Mail::shouldReceive('to->send')->once()->andThrow(new \RuntimeException('smtp down'));
        $this->artisan('pdf:check-stale')->assertExitCode(1);

        $this->assertTrue(Cache::add('pdf-disk-usage-stale-alert', true, 60));
    }
}
