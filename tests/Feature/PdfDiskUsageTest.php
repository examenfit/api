<?php

namespace Tests\Feature;

use App\Mail\PdfDiskUsageMail;
use App\Models\PdfDiskUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PdfDiskUsageTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/pdf-generator/disk-usage';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pdf_generator.disk_usage_token' => 'secret-token',
            'pdf_generator.disk_usage_critical_percentage' => 80,
            'pdf_generator.disk_usage_alert_to' => 'owner@example.com, second@example.com',
            'pdf_generator.disk_usage_alert_interval_hours' => 24,
        ]);
        Mail::fake();
    }

    private function reportUsage(array $data, string $token = 'secret-token')
    {
        return $this->postJson(self::URL, $data, ['X-Disk-Usage-Token' => $token]);
    }

    public function test_a_post_without_the_right_token_is_refused(): void
    {
        $this->postJson(self::URL, ['percentage' => 50])->assertForbidden();
        $this->reportUsage(['percentage' => 50], 'wrong')->assertForbidden();

        $this->assertSame(0, PdfDiskUsage::count());
    }

    public function test_the_endpoint_is_off_while_no_token_is_configured(): void
    {
        config(['pdf_generator.disk_usage_token' => '']);

        $this->reportUsage(['percentage' => 50], '')->assertForbidden();
    }

    public function test_a_measurement_is_stored(): void
    {
        $this->reportUsage(['percentage' => 73, 'used_mb' => 10720, 'available_mb' => 4068])
            ->assertCreated()
            ->assertJson(['critical' => false]);

        $this->assertDatabaseHas('pdf_disk_usages', ['percentage' => 73, 'used_mb' => 10720, 'available_mb' => 4068]);
    }

    public function test_the_sizes_are_optional(): void
    {
        $this->reportUsage(['percentage' => 12])->assertCreated();
    }

    public function test_a_percentage_outside_0_to_100_is_refused(): void
    {
        $this->reportUsage(['percentage' => 101])->assertUnprocessable();
        $this->reportUsage(['percentage' => 'vol'])->assertUnprocessable();
        $this->reportUsage([])->assertUnprocessable();
    }

    public function test_a_healthy_disk_sends_no_mail(): void
    {
        $this->reportUsage(['percentage' => 79])->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_a_critical_disk_mails_all_addresses_and_remembers_it(): void
    {
        $this->reportUsage(['percentage' => 80])->assertCreated()->assertJson(['critical' => true]);

        Mail::assertSent(PdfDiskUsageMail::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->hasTo('second@example.com'));
        $this->assertNotNull(PdfDiskUsage::first()->alerted_at);
    }

    public function test_while_it_stays_critical_there_is_one_mail_per_interval(): void
    {
        $this->reportUsage(['percentage' => 85]);
        $this->reportUsage(['percentage' => 86]);
        Mail::assertSent(PdfDiskUsageMail::class, 1);

        $this->travel(25)->hours();
        $this->reportUsage(['percentage' => 87]);

        Mail::assertSent(PdfDiskUsageMail::class, 2);
    }

    public function test_without_addresses_nothing_is_mailed_and_the_post_still_works(): void
    {
        config(['pdf_generator.disk_usage_alert_to' => '']);

        $this->reportUsage(['percentage' => 95])->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_the_overview_is_for_logged_in_admins_only(): void
    {
        $this->getJson('/api/admin/custom/pdf-generator/disk-usage')->assertUnauthorized();
    }

    public function test_the_overview_lists_the_measurements_newest_first_with_a_status(): void
    {
        PdfDiskUsage::create(['percentage' => 40, 'created_at' => now()->subDay()]);
        PdfDiskUsage::create(['percentage' => 90, 'used_mb' => 100, 'available_mb' => 10]);

        $rows = $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->getJson('/api/admin/custom/pdf-generator/disk-usage')
            ->assertOk()
            ->json();

        $this->assertSame([90, 40], array_column($rows, 'Gebruikt (%)'));
        $this->assertSame(['kritiek', 'ok'], array_column($rows, 'Status'));
    }

    public function test_the_overview_is_listed_in_the_custom_reports(): void
    {
        $reports = $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->getJson('/api/admin/custom/')
            ->assertOk()
            ->json();

        $this->assertContains('/api/admin/custom/pdf-generator/disk-usage', array_column($reports, 'endpoint'));
    }
}
