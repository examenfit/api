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
    '2022-W20', '2022-W21','2022-W22','2022-W23','2022-W24','2022-W25',
    //'2022-W26','2022-W27','2022-W28','2022-W29'
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

