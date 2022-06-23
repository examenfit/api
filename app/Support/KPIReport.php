<?php

namespace App\Support;

use App\Support\KPIs;

class KPIReport {

  const WEEKS = [
    '2021-W44','2021-W45','2021-W46','2021-W47','2021-W48','2021-W49',
    '2021-W50','2021-W51','2021-W52',
    '2022-W00','2022-W01','2022-W02','2022-W03','2022-W04','2022-W05','2022-W06','2022-W07','2022-W08','2022-W09',
    '2022-W10','2022-W11','2022-W12','2022-W13','2022-W14','2022-W15','2022-W16','2022-W17','2022-W18','2022-W19',
    '2022-W20', '2022-W21','2022-W22','2022-W23','2022-W24','2022-W25',
    //'2022-W26','2022-W27','2022-W28','2022-W29'
  ];

  public function report() {
    $kpi = new KPIs();

    $this->data = [];

    $this->add("Proeflicenties", $kpi->countProeflicenties(), $kpi->countProeflicentiesPerWeek());
    $this->add("Docentlicenties", $kpi->countDocentlicenties(), $kpi->countDocentlicentiesPerWeek());
    $this->add("Leerlinggebruikers", $kpi->countLeerlingSeats(), $kpi->countLeerlingSeatsPerWeek());
    $this->add("Leerlinggebruikers geactiveerd", $kpi->countActivatedLeerlingSeats());
    $this->add("Leerlinggebruikers niet geactiveerd", $kpi->countNonActivatedLeerlingSeats());
    $this->add("Unieke devices", $kpi->countDevices(), $kpi->countDevicesPerWeek());
    $this->add("Unieke devices met email", $kpi->countDevicesWithAccount());
    $this->add("Unieke devices zonder email", $kpi->countDevicesWithoutAccount());
    
    return $this->data;
  }

  function add($label, $count, $counts = []) {
    $record = [ 'KPI' => $label, 'Aantal' => $count ];
    foreach (KPIReport::WEEKS as $week) {
      if (array_key_exists($week, $counts)) {
        $record[$week] = $counts[$week];
      } else {
        $record[$week] = NULL;
      }
    }
    $this->data[] = $record;
  }

}
