<?php

namespace App\Console\Commands;

use Mail;
use App\Mail\InviteMail;

use App\Models\User;
use App\Models\Stream;
use App\Models\Group;
use App\Models\Seat;
use App\Models\Privilege;

use Illuminate\Console\Command;

ini_set("auto_detect_line_endings", true);

class ImportLeerlingen extends Command {

  protected $signature = 'ef:import:leerlingen {--docent=} {--file=} {--email=email} {--first_name=first_name} {--middle_name=} {--last_name=last_name} {--separator=} {--invite=}';
  protected $description = 'Imports leerlingen for a specific docent';

  public function handle() {
    $this->init();
    $this->import();
    $this->invite();
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

  function emptySeats() {
    $streams = [];
    foreach($this->streams as $stream) {
      $streams[] = $stream->id;
    };
    sort($streams);
    $empty = [];
    foreach ($this->license->seats as $seat) {
      if ($seat->role === 'leerling' && !$seat->email && !$seat->user) {
        $ids = [];
        foreach($seat->privileges as $priv) {
          if ($priv->action === 'oefensets uitvoeren') {
            $ids[] = $priv->object_id;
          }
        }
        if ($streams === $ids) {
          $empty[] = $seat;
        }
      }
    }
    return $empty;
  }

  function import() {
    $user = $this->docent;
    $this->info('Docent: '.$user->first_name.' '.$user->last_name.' <'.$user->email.'>');
    $this->info('Licentie: '.$this->license->description);
    $this->info('Groep: '.$this->group->name);
    foreach($this->streams as $stream) {
      $this->info('Vak/niveau: '.$stream->course->name.' '.$stream->level->name);
    }

    $empty = $this->emptySeats();
    
    $import = count($this->seats);
    $avail = count($empty);

    if ($avail < $import) {
    $this->warn('in te lezen: '.$import);
    $this->warn('beschikbaar: '.$avail);
      $diff = $import - $avail;
      $cont = $this->confirm("ontbrekende posities toevoegen?");
      if (!$cont) die();
    }

    $n = 0;
    $filled = 0;
    $added = 0;
    foreach ($this->seats as $seat) {
      $this->info('Leerling: '.$seat['first_name'].' '.$seat['last_name'].' <'.$seat['email'].'>');
      if ($n < count($empty)) {
        $empty[$n]->email = $seat['email'];
        $empty[$n]->first_name = $seat['first_name'];
        $empty[$n]->last_name = $seat['last_name'];
        $empty[$n]->save();
        $filled++;
      } else {
        $this->createSeat($seat);
        $added++;
      }
      ++$n;
    }
    $this->info('gevuld: '.$filled);
    $this->info('aangemaakt: '.$added);
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

  function invite() {
    if ($this->confirm('Uitnodigingen versturen?')) {
      foreach($this->seats as $to) {
        $seat = Seat::firstWhere('email', $to['email']);
        $user = $this->docent;
        $mail = new InviteMail($seat, $user);
        Mail::to($seat->email)->send($mail);
      }
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

    $sep = $this->option('separator');
    $lines = file($this->file);
    $names = explode($sep, array_shift($lines));

    $index = [];
    for ($column = 0; $column < count($names); ++$column) {
      $index[$column+1] = $column;
    }
    for ($column = 0; $column < count($names); ++$column) {
      $this->info($names[$column]);
      $index[$names[$column]] = $column;
    }

    $index['email'] = $index[$this->option('email')];
    $index['first_name'] = $index[$this->option('first_name')];
    $index['last_name'] = $index[$this->option('last_name')];

    if ($this->option('middle_name')) {
      $index['middle_name'] = $index[$this->option('middle_name')];
    }

    foreach($lines as $line) {
      $line = trim($line);
      if (!$line) {
        continue;
      }
      $values = explode($sep, $line);
      if (count($values) !== count($names)) {
        die('csv parse error');
      }
      if ($this->option('middle_name')) {
        $this->seats[] = [
          'email' => $values[$index['email']],
          'first_name' => $values[$index['first_name']],
          'last_name' => trim(
            strtolower($values[$index['middle_name']]).
            ' '.
            $values[$index['last_name']],
          )
        ];
      } else {
        $this->seats[] = [
          'email' => $values[$index['email']],
          'first_name' => $values[$index['first_name']],
          'last_name' => $values[$index['last_name']],
        ];
      }
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
