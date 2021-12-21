<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Stream;
use App\Models\Group;
use App\Models\Seat;
use App\Models\Privilege;

class ImportLeerlingen extends Command {

  protected $signature = 'ef:import:leerlingen {--docent=} {--file=} {--email=email} {--first_name=first_name} {--last_name=last_name}';
  protected $description = 'Imports leerlingen for a specific docent';

  public function handle() {
    $this->init();
    $this->import();
  }

  function abort($message) {
    $this->error($message);
    die();
  }

  function init() {
    $this->initDocent();
    $this->initFile();
  }

  function initDocent() {
    $user = User::firstWhere('email', $this->option('docent'));
    if (!$user) {
      $this->abort("gebruiker niet gevonden\n");
    }
    $seats = [];
    foreach($user->seats as $seat) {
      if ($seat->role === 'docent') {
        $seats[] = $seat;
      }
    }
    if (count($seats) !== 1) {
      $this->abort("verkeerd aantal gebruikerslicenties: ".count($seats)."\n");
    }
    $seat = $seats[0];
    $privileges = [];
    $streams = [];
    $groups = [];
    foreach($seat->privileges as $privilege) {
      if (str_ends_with($privilege->action, 'opgavensets samenstellen')) {
        $streams[] = Stream::find($privilege->object_id);
      }
      if (str_ends_with($privilege->action, 'groepen beheren')) {
        $groups[] = Group::find($privilege->object_id);
      }
    }
    if (count($streams) === 0) {
      $this->abort ("geen vakken/niveaus gevonden\n");
    }
    if (count($groups) !== 1) {
      $this->abort ("verkeerd aantal groepen: ".count($groups)."\n");
    }
    $this->docent = $user;
    $this->license = $seat->license;
    $this->streams = $streams;
    $this->group = $groups[0];
  }

  function initFile() {
    $this->file = $this->option('file');
    if (!$this->file) {
      return $this->readCli();
    }
    if (str_ends_with($this->file, 'xlsx')) {
      return $this->readXlsx($this->file);
    }
    if (str_ends_with($this->file, 'csv')) {
      return $this->readCsv($this->file);
    }
    $this->abort("onbekend type bestand\n");
  }

  function import() {
    $user = $this->docent;
    $this->info('Docent: '.$user->first_name.' '.$user->last_name.' <'.$user->email.'>');
    $this->info('Licentie: '.$this->license->description);
    $this->info('Groep: '.$this->group->name);
    foreach($this->streams as $stream) {
      $this->info('Vak/niveau: '.$stream->course->name.' '.$stream->level->name);
    }

    foreach ($this->seats as $seat) {
      $this->info('Leerling: '.$seat['first_name'].' '.$seat['last_name'].' <'.$seat['email'].'>');
      $this->createSeat($seat);
    }
  }

  function createSeat($data) {
    $seat = Seat::create([
      'license_id' => $this->license->id,
      'role' => 'leerling',
      'first_name' => $data['first_name'],
      'last_name' => $data['last_name'],
      'email' => $data['email'],
    ]);
    $seat->groups()->sync([ $this->group->id ]);
    foreach ($this->streams as $stream) {
      Privilege::create([
        'actor_seat_id' => $seat->id,
        'action' => 'oefensets uitvoeren',
        'object_type' => 'stream',
        'object_id' => $stream->id,
        'begin' => $this->license->begin,
        'end' => $this->license->end
      ]);
    }
  }

// CLI support

  function readCli() {
    $this->seats = [];
    for (;;) {
      $email = $this->ask('email');
      if (!$email) {
        return;
      }
      $first_name = $this->ask('first_name');
      $last_name = $this->ask('last_name');
      $this->seats[] = [
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
      ];
    }
  }

// CSV support

  function readCsv() {
    $this->seats = [];
    $f = fopen($this->file, 'r');
    if ($f === FALSE) {
      $this->abort("kan bestand niet openen");
    }
    $names = fgetcsv($f);
    $index = [];
    for ($column = 0; $column < count($names); ++$column) {
      $index[$column+1] = $column;
    }
    for ($column = 0; $column < count($names); ++$column) {
      $index[$names[$column]] = $column;
    }

    $index['email'] = $index[$this->option('email')];
    $index['first_name'] = $index[$this->option('first_name')];
    $index['last_name'] = $index[$this->option('last_name')];

    while ($values = fgetcsv($f)) {
      $this->seats[] = [
        'email' => $values[$index['email']],
        'first_name' => $values[$index['first_name']],
        'last_name' => $values[$index['last_name']],
      ];
    }
  }

// XLSX support

  function readXlsx() {
    $this->abort("not implemented yet\n");
    $this->initXlsx();
    $this->processXlsx();
  }

  function initXlsx() {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
    $xlsx = $reader->load($this->file);
    $this->sheets = $xlsx->getAllSheets();
  }

  function processXlsx() {
    foreach($this->sheets as $key => $sheet) {
      $this->sheet = $sheet;
      $title = strtolower($sheet->getTitle());
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
}
