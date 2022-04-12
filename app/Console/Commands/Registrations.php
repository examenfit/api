<?php

namespace App\Console\Commands;

use Throwable;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Registration;
use App\Models\Seat;
use App\Models\User;
use App\Models\License;

class Registrations extends Command {

  protected $signature = 'ef:registrations';

  const TEST_EMAIL_PATTERNS = [
    'examenfit',
    '@example.com',
    '@gielstekelenburg.nl',
    '@wismon.nl',
    'janaalfs@hotmail.com',
    'janaalfs2014@gmail.com',
    'marceldol@gmail.com',
    'vwesterlaak@gmail.com',
  ];

  public function handle() {
    $this->init();
    $this->process();
  }

  function init() {
    mb_internal_encoding('UTF-8');
  }

  function process() {
    fprintf(STDOUT, "Datum\tAanvraag\tEmail\tInfo");
    fprintf(STDOUT, "\tSchool");
    fprintf(STDOUT, "\tLicentie\tVan\tTot\t#Docenten\t#Leerlingen");
    foreach(Registration::all() as $registration) {
      $this->reportRegistration($registration);
    }
    fprintf(STDOUT, "\n");
  }

  function reportRegistration($registration) {
    $isTest = $this->isTest($registration);
    fprintf(STDOUT, "\n%s\t%s\t%s",
      substr($registration->created_at, 0, 10),
      $registration->license,
      $registration->email
    );
    if ($isTest) {
      return fprintf(STDOUT, "\t%s", 'TEST');
    } elseif ($registration->activated) {
      $user = User::firstWhere('email', $registration->email);
      if ($user) {
        fprintf(STDOUT, "\t%s", '');
      } else {
        return fprintf(STDOUT, "\t%s", 'MISMATCH');
      }
      foreach($user->seats as $seat) {
        foreach($seat->privileges as $priv) {
          if ($priv->action = 'licentie beheren') {
            return $this->reportSeat($seat);
          }
        }
      }
    }
  }

  function reportSeat($seat) {
    if ($seat->user) {
      $this->reportUser($seat->user);
      $this->reportLicense($seat->license);
    }
  }

  function reportUser($user) {
    if ($user->data) {
      $data = json_decode($user->data);
      if (property_exists($data, 'school')) {
        return fprintf(STDOUT, "\t%s", $data->school);
      }
    }
    return fprintf(STDOUT, "\t%s", $user->email);
  }

  function reportLicense($license) {
    fprintf(STDOUT, "\t%s\t%s\t%s\t%d\t%d",
      $license->type,
      substr($license->begin, 0, 10),
      substr($license->end, 0, 10),
      count($license->seats->where('role', 'docent')),
      count($license->seats->where('role', 'leerling')),
    );
  }

  function isTest($registration) {
    foreach(Registrations::TEST_EMAIL_PATTERNS as $pattern) {
      if (strpos($registration->email, $pattern) !== FALSE) {
        return 'TEST';
      }
    }
    return '';
  }
}
