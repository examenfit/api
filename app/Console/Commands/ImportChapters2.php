<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Methodology;

class ImportChapters2 extends Command {

  protected $signature = 'ef:import:chapters2 {--dir=../meta} {--file=} {vak} {niveau}';
  protected $description = 'Imports an Excel sheet';

  const COURSE = [
    'natuurkunde' => 'Natuurkunde',
    'wiskunde'  => 'Wiskunde',
    'wiskunde-a'  => 'Wiskunde A',
    'wiskunde-b'  => 'Wiskunde B',
  ];

  const LEVEL = [
    'vmbo' => 'Vmbo GT',
    //'vmbo' => 'Vmbo GL en TL',
    'havo' => 'Havo',
    'vwo' => 'Vwo',
  ];

  public function handle() {
    $this->init();
    $this->process();
  }

  function init() {
    mb_internal_encoding('UTF-8');
    $this->initVak();
    $this->initNiveau();
    $this->initStream();
    $this->initMethodologies();
    $this->initFile();
    $this->initXlsx();
  }

  function initVak() {
    $vak = $this->argument('vak');
    if (array_key_exists($vak, ImportChapters::COURSE)) {
      $this->vak = $vak;
    } else {
      die("Vak {$vak} niet gevonden.\n\n");
    }
  }

  function initNiveau() {
    $niveau = $this->argument('niveau');
    if (array_key_exists($niveau, ImportChapters::LEVEL)) {
      $this->niveau = $niveau;
    } else {
      die("Niveau {$niveau} niet gevonden.\n\n");
    }
  }

  function initStream() {
    $streams = $this->queryStreams();
    if (count($streams)) {
      $this->stream = $streams[0];
    } else {
      die("Stroom {$vak}-{$niveau} niet gevonden.\n\n");
    }
  }

  function queryStreams() {
    return DB::select(ImportChapters::QUERY_STREAMS, [
      ImportChapters::COURSE[$this->vak],
      ImportChapters::LEVEL[$this->niveau]
    ]);
  }

  const QUERY_STREAMS = "
    SELECT
      streams.id,
      streams.course_id,
      streams.level_id
    FROM
      courses,
      levels,
      streams
    WHERE
      courses.name = ? AND
      courses.id = streams.course_id AND
      levels.name = ? AND
      levels.id = streams.level_id
  ";

  function initMethodologies() {
    $methodologies = Methodology::all();
    foreach($methodologies as $methodology) {
      $name = $methodology->name;
      $this->methodologies[$name] = $methodology->id;
    }
    var_dump($this->methodologies);
  }

  function initFile() {
    if ($this->option('file')) {
      $this->file = $this->option('file');
    } else {
      $dir = $this->option('dir');
      $this->file = "{$dir}/{$this->vak}-{$this->niveau}-chapters.xlsx"; 
    }
  }

  function initXlsx() {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
    $xlsx = $reader->load($this->file);
    $this->sheets = $xlsx->getAllSheets();
  }

  function process() {
    foreach($this->sheets as $key => $sheet) {
      $this->sheet = $sheet;
      $title = mb_strtolower($sheet->getTitle());
      preg_match('/\\b(\\d{4})-(\\d{1})\\b/', $title, $matches);
      if ($matches) {
        $this->year = $matches[1];
        $this->term = $matches[2];
        $this->import();
      }
    }
  }

  function import()
  {
    $vraagnr = 0;
    $nova = 0;
    $newton4 = 0;
    $newton5 = 0;
    $overal4 = 0;
    $overal5 = 0;
    $sysnat = 0;
    $gr12 = 0;
    $mw12 = 0;

    $this->info($this->year.'-'.$this->term);
    for ($x = 1; $x < 99; $x += 1) {
      $name = mb_strtolower($this->getValue($x,1));
      $druk = mb_strtolower($this->getValue($x,2));
      if ($name === 'vraag nr.') {
        $vraagnr = $x;
      } else if ($name === 'hoofdstuk nova') {
        if (array_key_exists('Nova 2019-2021 ed.', $this->methodologies)) {
          $nova = $x;
        } else {
          $this->info('"Nova 2019-2021 ed." niet beschikbaar');
        }
      } else if ($name === 'hoofdstuk sysnat') {
        if (array_key_exists('Systematische Natuurkunde 8 ed.', $this->methodologies)) {
          $sysnat = $x;
        } else {
          $this->info('"Systematische Natuurkunde 8 ed." niet beschikbaar');
        }
      } else if ($name === 'hoofdstuk overal') {
        if ($druk === '5de druk') {
          if (array_key_exists('Overal Natuurkunde 5 ed.', $this->methodologies)) {
            $overal5 = $x;
          } else {
            $this->info('"Overal Natuurkunde 5 ed." niet beschikbaar');
          }
        } else {
          if (array_key_exists('Overal Natuurkunde 4 ed.', $this->methodologies)) {
            $overal4 = $x;
          } else {
            $this->info('"Overal Natuurkunde 4 ed." niet beschikbaar');
          }
        }
      } else if ($name === 'hoofdstuk newton') {
        if ($druk === '5de druk') {
          if (array_key_exists('Newton 5 ed.', $this->methodologies)) {
            $newton5 = $x;
          } else {
            $this->info('"Newton 5 ed." niet beschikbaar');
          }
        } else {
          if (array_key_exists('Newton 4 ed.', $this->methodologies)) {
            $newton4 = $x;
          } else {
            $this->info('"Newton 4 ed." niet beschikbaar');
          }
        }
      } else if ($name === 'hoofdstuk g&r 12e editie') {
        if (array_key_exists('Getal & Ruimte 12 ed.', $this->methodologies)) {
          $gr12 = $x;
        } else {
          $this->info('"Getal & Ruimte 12 ed." niet beschikbaar');
        }
      } else if ($name === 'hoofdstuk mw 12e editie') {
        if (array_key_exists('Moderne Wiskunde 12 ed.', $this->methodologies)) {
          $mw12 = $x;
        } else {
          $this->info('"Moderne Wiskunde 12 ed." niet beschikbaar');
        }
      } else if (str_starts_with($name, 'hoofdstuk ')) {
        $this->info("\"$name\" onbekend...");
      }
    }

    if (!$vraagnr) {
      $this->info('"Vraag Nr." field not found');
      return;
    }

    for ($y = 2; $y < 99; $y += 1) {
      $number = $this->getValue($vraagnr, $y);
      if (!$number) {
        continue;
      }

      if ($nova) {
        $this->importChapters('Nova 2019-2021 ed.', $number, $this->getValue($nova, $y));
      }
      if ($newton4) {
        $this->importChapters('Newton 4 ed.', $number, $this->getValue($newton4, $y));
      }
      if ($newton5) {
        $this->importChapters('Newton 5 ed.', $number, $this->getValue($newton5, $y));
      }
      if ($overal4) {
        $this->importChapters('Overal Natuurkunde 4 ed.', $number, $this->getValue($overal4, $y));
      }
      if ($overal5) {
        $this->importChapters('Overal Natuurkunde 5 ed.', $number, $this->getValue($overal5, $y));
      }
      if ($sysnat) {
        $this->importChapters('Systematische Natuurkunde 8 ed.', $number, $this->getValue($sysnat, $y));
      }
      if ($gr12) {
        $this->importChapters('Getal & Ruimte 12 ed.', $number, $this->getValue($gr12, $y), 2);
      }
      if ($mw12) {
        $this->importChapters('Moderne Wiskunde 12 ed.', $number, $this->getValue($mw12, $y), 2);
      }
    }
  }

  function importChapters($methodology, $number, $str, $type = 1) {
    $this->question = $this->getQuestion($number);
    if (!$this->question) {
      $this->info("Vraag niet gevonden: $number");
      return;
    }
    $this->methodology_id = $this->methodologies[$methodology];
    DB::delete("
      DELETE FROM
        question_chapter
      WHERE
        question_id = ? AND
        chapter_id IN (
          SELECT id FROM chapters WHERE methodology_id = ?
        )
    ", [
      $this->question->id,
      $this->methodology_id
    ]);

    if (trim($str)) {
      $year = $this->year;
      $term = $this->term;
      $chapters = explode("\n", trim($str));
      $this->info($number.' '.join(',',$chapters).' ('.$methodology.')');
      foreach($chapters as $name) {
        if ($type === 1) {
          $chapter = $this->getChapter($name);
        }
        if ($type === 2) {
          $chapter = $this->getChapter2($name);
        }
        if ($chapter) {
          DB::insert("INSERT INTO question_chapter SET question_id = ?, chapter_id = ?", [$this->question->id, $chapter->id]);
        } else {
          $this->info($name.' ('.$methodology.') not found');
        }
      }
    }
  }

  function getQuestion($number) {
    $r = $this->queryQuestion($number);
    if (count($r)) {
      return $r[0];
    } else {
      return null;
    }
  }

  function queryQuestion($number) {
    return DB::select("
        SELECT questions.id
        FROM questions, topics, exams
        WHERE year = ? AND
              term = ? AND
              stream_id = ? AND
              exam_id = exams.id AND
              topic_id = topics.id AND
              number = ?
    ", [
        $this->year, $this->term, $this->stream->id, $number
    ]);
  }

  function getChapter($name) {
    $r = $this->queryChapter($name);
    if (count($r)) {
      return $r[0];
    } else {
      return null;
    }
  }

  function getChapter2($import_id) {
    $r = $this->queryChapter2($import_id);
    if (count($r)) {
      return $r[0];
    } else {
      return null;
    }
  }

  function queryChapter($name) {
    return DB::select("
        SELECT id, name, title
        FROM chapters
        WHERE methodology_id = ? AND
              stream_id = ? AND
              name = ?
    ", [
        $this->methodology_id, $this->stream->id, $name
    ]);
  }

  function queryChapter2($import_id) {
    return DB::select("
        SELECT id, name, title
        FROM chapters
        WHERE methodology_id = ? AND
              stream_id = ? AND
              import_id = ?
    ", [
        $this->methodology_id, $this->stream->id, $import_id
    ]);
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
