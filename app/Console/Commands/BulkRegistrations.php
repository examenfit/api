<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\RegistrationController;

class ImportOefensets extends Command {

  protected $signature = 'ef:bulk:registrations {--file=}';
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
    return TRUE;
  }

  function import()
  {
    $sys = new RegistrationController();

    if (!$this->validateSheet()) {
      return; // quit
    }
    $n = 0;
    for ($row = 2; $row < 999; $row += 1) {
      $email = $this->getValue(2, $row);
      $first_name = $this->getValue(3, $row) ?: "";
      $last_name = $this->getValue(4, $row) ?: "";
      if ($email) {
        $this->info("$first_name $last_name <$email>");
        $sys->process([
          'license' => 'proeflicentie',
          'first_name' => $first_name,
          'last_name' => $last_name,
          'email' => $email,
        ]);
        $n++;
      }
    }
    $this->info("$n registraties verzonden");
  }

}
