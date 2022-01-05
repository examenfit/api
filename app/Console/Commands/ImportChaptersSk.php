<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Methodology;

class ImportChaptersSk extends Command {

  protected $signature = 'ef:import:chapters-sk {--dir=../meta} {--file=} {vak} {niveau}';
  protected $description = 'Imports an Excel sheet';

  const COURSE = [
    'scheikunde' => 'Scheikunde',
  ];

  const LEVEL = [
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
    if (array_key_exists($vak, ImportChaptersSk::COURSE)) {
      $this->vak = $vak;
    } else {
      die("Vak {$vak} niet gevonden.\n\n");
    }
  }

  function initNiveau() {
    $niveau = $this->argument('niveau');
    if (array_key_exists($niveau, ImportChaptersSk::LEVEL)) {
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
    return DB::select(ImportChaptersSk::QUERY_STREAMS, [
      ImportChaptersSk::COURSE[$this->vak],
      ImportChaptersSk::LEVEL[$this->niveau]
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
  }

  function initFile() {
    if ($this->option('file')) {
      $this->file = $this->option('file');
    } else {
      $dir = $this->option('dir');
      $this->file = "{$dir}/{$this->vak}-{$this->niveau}-chapters.xlsx"; 
    }
    $this->info($this->file);
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
      $this->info("sheet: $title");
      preg_match('/^(\\d{4})-(i{1,3})$/', $title, $matches);
      if ($matches) {
        $this->year = $matches[1];
        $this->term = strlen($matches[2]);
        $this->import();
      }
    }
  }

  function import()
  {
    $vraagnr = 0;
    $nova2016 = 0;
    $nova2019 = 0;
    $co5 = 0;
    $chemie7 = 0;

    $this->info($this->year.'-'.$this->term);
    for ($x = 1; $x < 99; $x += 1) {
      $name = mb_strtolower($this->getValue($x,2));
      if ($name === 'vraag nr.') {
        $vraagnr = $x;
      } else if ($name === 'nova max' && $this->niveau === 'havo') {
        if (array_key_exists('Nova Scheikunde 2016-2018 ed.', $this->methodologies)) {
          $nova2016 = $x;
        } else {
          $this->info('"Nova Scheikunde 2016-2018 ed." niet beschikbaar');
        }
      } else if ($name === 'nova max' && $this->niveau === 'vwo') {
        if (array_key_exists('Nova Scheikunde 2019-2021 ed.', $this->methodologies)) {
          $nova2019 = $x;
        } else {
          $this->info('"Nova Scheikunde 2019-2021 ed." niet beschikbaar');
        }
      } else if ($name === 'co 5e ed.') {
        if (array_key_exists('Chemie Overal 5 ed.', $this->methodologies)) {
          $co5 = $x;
        } else {
          $this->info('"Chemie Overal 5 ed." niet beschikbaar');
        }
      } else if ($name === 'chemie 7e ed.') {
        if (array_key_exists('Chemie 7 ed.', $this->methodologies)) {
          $chemie7 = $x;
        } else {
          $this->info('"Chemie 7 ed." niet beschikbaar');
        }
      } else if ($name) {
        $this->info("Kolom \"$name\" genegeerd.");
      }
    }

    if (!$vraagnr) {
      $this->info('"Vraag Nr." field not found');
      return;
    }

    for ($y = 3; $y < 99; $y += 1) {
      $number = $this->getValue($vraagnr, $y);
      if (!$number) {
        continue;
      }
      if ($nova2019) {
        $this->importChapters('Nova Scheikunde 2019-2021 ed.', $number, $this->getValue($nova2019, $y));
      }
      if ($nova2016) {
        $this->importChapters('Nova Scheikunde 2016-2018 ed.', $number, $this->getValue($nova2016, $y));
      }
      if ($co5) {
        $this->importChapters('Chemie Overal 5 ed.', $number, $this->getValue($co5, $y));
      }
      if ($chemie7) {
        $this->importChapters('Chemie 7 ed.', $number, $this->getValue($chemie7, $y));
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
      $chapters = explode(",", trim($str));
      $this->info('Vraag '.$number.' '.join(',',$chapters).' ('.$methodology.')');
      foreach($chapters as $name) {
        $name = trim($name);
        if ($type === 1) {
          $chapter = $this->getChapter("H$name");
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
