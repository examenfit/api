<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\RegistrationController;

class BulkLeerlingRegistrations extends Command {

  protected $signature = 'ef:bulk:leerling-registrations {--file=} {--payment_status=}';
  protected $description = 'Imports an Excel sheet';

  public function handle() {
    $this->init();
    $this->process();
  }

  function init() {
    mb_internal_encoding('UTF-8');
    $this->initFile();
    $this->initXlsx();
  }

  function initFile() {
    $this->file = $this->option('file');
  }

  function initXlsx() {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
    $xlsx = $reader->load($this->file);
    $this->sheets = $xlsx->getAllSheets();
  }

  function process() {
    foreach($this->sheets as $key => $sheet) {
      $this->sheet = $sheet;
      $this->import();
    }
  }

  function getValue($col, $row, $default = null) {
    $value = $this->sheet->getCellByColumnAndRow($col, $row)->getValue();
    if ($value !== null) {
      return $value;
    } else {
      return $default;
    }
  }

  function validateSheet()
  {
    // todo verify layout
    if (mb_strtolower($this->getValue(2,1)) !== 'email') {
      $this->error("'Email' verwacht in B1");
      return;
    }
    if (mb_strtolower($this->getValue(3,1)) !== 'first name') {
      $this->error("'First Name' verwacht in C1");
      return;
    }
    if (mb_strtolower($this->getValue(4,1)) !== 'last name') {
      $this->error("'Last Name' verwacht in D1");
      return;
    }
    if (mb_strtolower($this->getValue(5,1)) !== 'streams') {
      $this->error("'Last Name' verwacht in D1");
      return;
    }
    return TRUE;
  }

  function getEmail($row)
  {
    return $this->getValue(2, $row);
  }

  function getFirstName($row)
  {
    return $this->getValue(3, $row);
  }

  function getLastName($row)
  {
    return $this->getValue(4, $row);
  }

  function getStreamSlugs($row)
  {
    return explode(',', $this->getValue(5, $row));
  }

  function import()
  {
    // todo leerling
    $sys = new RegistrationController();

    if (!$this->validateSheet()) {
      return; // quit
    }
    $n = 0;
    for ($row = 2; $row < 999; $row += 1) {
      $email = $this->getEmail($row);
      $first_name = $this->getFirstName($row);
      $last_name = $this->getLastName($row);
      $stream_slugs = $this->getStreamSlugs($row);
      $license = 'leerlinglicentie-' . count($stream_slugs);
      if ($email) {
        $this->info("$first_name $last_name <$email>");
        $sys->processLeerling([
          'license' => $license,
          'stream_slugs' => json_encode($stream_slugs),
          'first_name' => $first_name,
          'last_name' => $last_name,
          'email' => $email,
          'payment_status' => 'paid'
        ]);
        $n++;
      }
    }
    $this->info("$n registraties verzonden");
  }

}
