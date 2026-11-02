<?php

namespace Tests\Feature;

use App\Mail\PdfServerFailureMail;
use App\Support\RemoteScriptFailureAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RemoteScriptFailureAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pdf_generator.disk_usage_alert_to' => 'owner@example.com, second@example.com',
            'pdf_generator.failure_alert_interval_hours' => 6,
        ]);
        Cache::flush();
        Mail::fake();
    }

    private function sendFailure(): bool
    {
        return (new RemoteScriptFailureAlert)->send('ssh examenfit@pdf make/pdf abc', 255, 'Host key verification failed.');
    }

    public function test_the_owners_get_a_mail_with_command_status_and_output()
    {
        $this->assertTrue($this->sendFailure());

        Mail::assertSent(PdfServerFailureMail::class, fn ($mail) => $mail->hasTo('owner@example.com')
            && $mail->hasTo('second@example.com')
            && str_contains($mail->render(), 'Host key verification failed.')
            && str_contains($mail->render(), 'make/pdf abc')
            && str_contains($mail->render(), '255'));
    }

    public function test_a_second_failure_within_the_interval_sends_no_second_mail()
    {
        $this->sendFailure();

        $this->assertFalse($this->sendFailure());

        Mail::assertSent(PdfServerFailureMail::class, 1);
    }

    public function test_after_the_interval_the_next_failure_mails_again()
    {
        $this->sendFailure();
        $this->travel(7)->hours();

        $this->assertTrue($this->sendFailure());

        Mail::assertSent(PdfServerFailureMail::class, 2);
    }

    public function test_without_addresses_nothing_is_sent()
    {
        config(['pdf_generator.disk_usage_alert_to' => '']);

        $this->assertFalse($this->sendFailure());

        Mail::assertNothingSent();
    }

    public function test_a_broken_mail_server_does_not_throw_and_does_not_use_up_the_interval()
    {
        $this->useUnreachableSmtpServer();
        $this->assertFalse($this->sendFailure());

        Mail::fake();
        $this->assertTrue($this->sendFailure());
    }

    // nothing listens on port 1: the send fails at once
    private function useUnreachableSmtpServer(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        Mail::clearResolvedInstances();
        app()->forgetInstance('mail.manager');
    }
}
