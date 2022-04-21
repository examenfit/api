<?php

namespace App\Console\Commands;

use Mail;
use App\Mail\BulkLeerlingReminderMail;
use App\Models\Registration;

use Illuminate\Console\Command;

class SendReminder extends Command {

  protected $signature = 'ef:send:leerling-reminder {email}';
  protected $description = 'Sends BulkLeerlingReminderMail to all matching Registrations that are not activated';

  public function handle() {
    $this->init();
    $this->process();
  }

  function init() {
    $this->email = $this->argument('email');
  }

  function process() {
    $registrations = $this->getRegistrations();
    $count = count($registrations);
    $this->info("{$count} registrations found.");
    foreach($registrations as $registration) {
      $this->info("{$registration->email}");
      $this->sendReminder($registration);
    }
  }

  function getRegistrations() {
    $registrations = Registration::query()
      ->where('email', 'LIKE', $this->email)
      ->where('license', 'LIKE', 'leerlinglicentie%')
      ->whereNull('activated')
      ->get();
    return $registrations;
  }

  function sendReminder($registration) {
    $mail = new BulkLeerlingReminderMail($registration);
    Mail::to($registration->email)->send($mail);
  }

}
