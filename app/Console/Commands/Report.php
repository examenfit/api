<?php

namespace App\Console\Commands;

use Throwable;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Registration;
use App\Models\User;
use App\Models\License;

class Report extends Command {

  protected $signature = 'ef:report';

  const SKIP_EMAIL = [
    '@examenfit.nl',
    '@example.com',
    '@gielstekelenburg.nl',
    '@wismon.nl',
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
    $this->reportSchools();
    $this->reportRegistraties();
    $this->reportLicenties();
  }

  function skipEmail($u) {
    $email = $u->email;
    foreach (Report::SKIP_EMAIL as $pattern) {
      if (strpos($email, $pattern) !== FALSE) {
        return TRUE;
      }
    }
  }

  function reportSchools() {
    $counts = [];
    $source = [];
    foreach(User::all() as $u) {
      if ($u->role != 'docent') continue;
      if ($this->skipEmail($u)) continue;
      try {
        $data = json_decode($u->data);
        $school = $data->school;
      } catch(Throwable $t) {
        $school = $u->email;
      }
      //$k = mb_strtolower($school);
      $k = $school;
      if (array_key_exists($k, $counts)) {
        $counts[$k]++;
      } else {
        $counts[$k] = 1;
      }
    }
    $this->reportCounts($counts, 'School');
  }

  function reportLicenties() {
    $counts = [];
    foreach(License::all() as $l) {
      $u = $l->seats->firstWhere('role', 'docent');
      if ($u && $this->skipEmail($u)) continue;
      $k = $l->type;
      if (array_key_exists($k, $counts)) {
        $counts[$k]++;
      } else {
        $counts[$k] = 1;
      }
    }
    $this->reportCounts($counts, 'Licentie');
  }

  function reportRegistraties() {
    $counts = [];
    foreach(Registration::all() as $r) {
      if ($this->skipEmail($r)) continue;
      $k = $r->license;
      if (array_key_exists($k, $counts)) {
        $counts[$k]++;
      } else {
        $counts[$k] = 1;
      }
    }
    $this->reportCounts($counts, 'Aanmelding');

    $counts = [];
    foreach(Registration::all() as $r) {
      if ($this->skipEmail($r)) continue;
      if (!$r->activated) continue;
      $k = $r->license;
      if (array_key_exists($k, $counts)) {
        $counts[$k]++;
      } else {
        $counts[$k] = 1;
      }
    }
    $this->reportCounts($counts, 'Aanmelding (geactiveerd)');
  }

  function reportCounts($counts, $label) {
    ksort($counts);
    $total = 0;
    $this->reportHeader('Aantal', $label);
    foreach($counts as $school => $count) {
      $this->reportCount($count, $school);
      $total += $count;
    }
    $this->reportCount($total, 'Totaal');
    fprintf(STDOUT, "\n");
  }

  function reportHeader($count, $label) {
      fprintf(STDOUT, "%s\t%s\n", $label, $count);
  }

  function reportCount($count, $label) {
      fprintf(STDOUT, "%s\t%d\n", $label, $count);
  }

}
