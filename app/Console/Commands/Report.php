<?php

namespace App\Console\Commands;

use Throwable;

use Illuminate\Console\Command;

use App\Support\KPIs;

class Report extends Command {

  protected $signature = 'ef:report';

  const WEEKS = [
    '2021-W44','2021-W45','2021-W46','2021-W47','2021-W48','2021-W49',
    '2021-W50','2021-W51','2021-W52',
    '2022-W00','2022-W01','2022-W02','2022-W03','2022-W04','2022-W05','2022-W06','2022-W07','2022-W08','2022-W09',
    '2022-W10','2022-W11','2022-W12','2022-W13','2022-W14','2022-W15','2022-W16','2022-W17','2022-W18','2022-W19',
    '2022-W20',
//'2022-W21','2022-W22','2022-W23','2022-W24','2022-W25','2022-W26','2022-W27','2022-W28','2022-W29'
  ];

  public function handle() {
    $weeks = implode("\t", Report::WEEKS);
    printf("KPI\tTotaal\t$weeks");

    $kpi = new KPIs();
    $this->report("Proeflicenties", $kpi->countProeflicenties(), $kpi->countProeflicentiesPerWeek());
    $this->report("Docentlicenties", $kpi->countDocentlicenties(), $kpi->countDocentlicentiesPerWeek());
    $this->report("Leerlinggebruikers", $kpi->countLeerlingSeats(), $kpi->countLeerlingSeatsPerWeek());
    $this->report("Leerlinggebruikers geactiveerd", $kpi->countActivatedLeerlingSeats());
    $this->report("Leerlinggebruikers niet geactiveerd", $kpi->countNonActivatedLeerlingSeats());
    $this->report("Unieke devices", $kpi->countDevices(), $kpi->countDevicesPerWeek());
    $this->report("Unieke devices met email", $kpi->countDevicesWithAccount());
    $this->report("Unieke devices zonder email", $kpi->countDevicesWithoutAccount());
    printf("\n");
  }

  function report($label, $count, $counts = []) {
    printf("\n$label\t$count");
    foreach (Report::WEEKS as $week) {
      if (array_key_exists($week, $counts)) {
        printf("\t%d", $counts[$week]);
      } else {
        printf("\t");
      }
    }
  }
}

/*
use Illuminate\Support\Facades\DB;

use App\Models\Registration;
use App\Models\User;
use App\Models\License;

class Report extends Command {

  protected $signature = 'ef:report';

  const SKIP_EMAIL = [
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
    $this->reportSchools();
    $this->reportRegistraties();
    $this->reportLicenties();
    $this->reportSeats();
  }

  function skipUserEmail($user) {
    $email = $user->email;
    return $this->skipEmail($email);
  }

  function skipEmail($email) {
    //fprintf(STDOUT, "\nskipUserEmail %s?", $email);
    foreach (Report::SKIP_EMAIL as $pattern) {
      if (strpos($email, $pattern) !== FALSE) {
        //fprintf(STDOUT, "\nSKIP: '%s'", $pattern);
        return TRUE;
      }
    }
  }

  function reportSchools() {
    $counts = [];
    $source = [];
    foreach(User::all() as $u) {
      if ($u->role != 'docent') continue;
      if ($this->skipUserEmail($u)) continue;
      try {
        $data = json_decode($u->data);
        $school = $data->school;
      } catch(Throwable $t) {
        $school = $u->email;
      }
      //$k = mb_strtolower($school);
      $k = $school;
      $this->inc($counts, $k);
    }
    $this->reportCounts($counts, 'School');
  }

  function reportLicenties() {
    $counts = [];
    foreach(License::all() as $l) {
      if ($this->skipLicense($l)) continue;
      $k = $l->type;
      $this->inc($counts, $k);
    }
    $this->reportCounts($counts, 'Licentie');
  }

  function skipLicense($l) {
    if ($l->type === 'leerlinglicentie') {
      return $this->skipUserEmail($l->seats[0]->user);
    }
    foreach($l->seats as $seat) {
      $p = $seat->privileges->firstWhere('action', 'licentie beheren');
      if ($p) {
        if ($this->skipUserEmail($seat->user)) return TRUE;
      }
    }
  }

  function reportRegistraties() {
    $counts = [];
    foreach(Registration::all() as $r) {
      if ($this->skipUserEmail($r)) continue;
      $k = $r->license;
      $this->inc($counts, $k);
    }
    $this->reportCounts($counts, 'Aanmelding');

    $counts = [];
    foreach(Registration::all() as $r) {
      if ($this->skipUserEmail($r)) continue;
      if (!$r->activated) continue;
      $k = $r->license;
      $this->inc($counts, $k);
    }
    $this->reportCounts($counts, 'Aanmelding (geactiveerd)');
  }

  function reportSeats() {
    foreach(License::all() as $license) {
      $month = substr($license->created_at, 0, 7);
      $type = $license->type;
      foreach($license->seats as $seat) {
        $role = $seat->role;
        die($role);
      }
    }
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

  function inc(&$index, $k) {
    if (array_key_exists($k, $index)) {
      $index[$k]++;
    } else {
      $index[$k] = 1;
    }
  }
}
*/
