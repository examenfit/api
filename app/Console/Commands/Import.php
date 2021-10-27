<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Import extends Command {

  protected $signature = 'ef:import {--dir=../meta} {--file=} {vak} {niveau}';
  protected $description = 'Clears and imports meta data based on an Excel sheet';

  const COURSE = [
    'natuurkunde' => 'Natuurkunde',
    'wiskunde'  => 'Wiskunde',
    'wiskunde-a'  => 'Wiskunde A',
    'wiskunde-b'  => 'Wiskunde B',
  ];

  const LEVEL = [
    'havo' => 'Havo',
    'vmbo' => 'Vmbo GL en TL',
    'vwo' => 'Vwo',
  ];

  public function handle() {
    $this->init();
    $this->process();
  }

  function init() {
    $this->initVak();
    $this->initNiveau();
    $this->initStream();
    $this->initFile();
    $this->initXlsx();
  }

  function initVak() {
    $vak = $this->argument('vak');
    if (array_key_exists($vak, Import::COURSE)) {
      $this->vak = $vak;
    } else {
      die("Vak {$vak} niet gevonden.\n\n");
    }
  }

  function initNiveau() {
    $niveau = $this->argument('niveau');
    if (array_key_exists($niveau, Import::LEVEL)) {
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

  function queryStreams() {
    return DB::select(Import::QUERY_STREAMS, [
      Import::COURSE[$this->vak],
      Import::LEVEL[$this->niveau]
    ]);
  }

  function initFile() {
    if ($this->option('file')) {
      $this->file = $this->option('file');
    } else {
      $dir = $this->option('dir');
      $this->file = "{$dir}/{$this->vak}-{$this->niveau}.xlsx"; 
    }
  }

  function initXlsx() {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
    $xlsx = $reader->load($this->file);
    $this->sheets = $xlsx->getAllSheets();
  }

  function process() {
    $this->processReferenceData();
    $this->processMetaData();
  }

  function processReferenceData() {
    foreach($this->sheets as $key => $sheet) {
      $this->sheet = $sheet;
      $title = strtolower($sheet->getTitle());
      if ($title === 'domeinen') {
        $this->processDomeinen($sheet);
      }
      if ($title === 'trefwoorden') {
        $this->processTrefwoorden($sheet);
      }
      if ($title === 'vraagtypen') {
        $this->processVraagtypen($sheet);
      }
    }
  }

  function processDomeinen() {
  }

  function processTrefwoorden() {
  }

  function processVraagtypen() {
  }

  function processMetaData() {
    foreach($this->sheets as $key => $sheet) {
      $this->sheet = $sheet;
      if ($this->initTerm()) {
        $this->handleExam();
      } else {
        $title = strtolower($sheet->getTitle());
        if ($title !== 'domeinen' && $title !== 'trefwoorden' && $title !== 'vraagtypen') {
          $this->warn("Onbekend werkblad '{$sheet->getTitle()}' genegeerd.");
        }
      }
    }
  }

  const MATCH_TERM = '/^(\d{4})-(\d)/';

  function initTerm() {
    $matches = [];
    preg_match(Import::MATCH_TERM, $this->sheet->getTitle(), $matches);

    if (count($matches)) {
      $this->year = +$matches[1];
      $this->term = +$matches[2];
      return true;
    }
  }

  function handleExam() {
    if ($this->initExam()) {
      $this->processExam();
    } else {
      $this->info("Werkblad '{$this->sheet->getTitle()}' genegeerd.");
    }
  }

  function initExam() {
    $exams = $this->queryExams();
    if (count($exams)) {
      $this->exam = $exams[0];
      $status = $this->exam->status;
      if ($status === 'published' || $status === 'concept') {
        return true;
      } else {
        $this->error("Examen {$this->year}-{$this->term} heeft status '{$status}'.");
      }
    } else {
      $this->info("Examen {$this->year}-{$this->term} niet gevonden.");
    }
  }

  const QUERY_EXAMS = "
    SELECT 
      *
    FROM
      exams
    WHERE
      year = ? AND
      term = ? AND
      stream_id = ?
  ";

  function queryExams() {
    return DB::select(Import::QUERY_EXAMS, [
      $this->year,
      $this->term,
      $this->stream->id
    ]);
  }

  function processExam() {
    if ($this->initExamHeaders()) {
      if ($this->initExamQuestions()) {
        if ($this->vraagtypen) {
          $this->processExamVraagtypen();
        }
        if ($this->domeinen) {
          $this->processExamDomeinen();
        }
        if ($this->trefwoorden) {
          $this->processExamTrefwoorden();
        }
        if ($this->highlights) {
          $this->processExamHighlights();
        }
        if ($this->vaardigheid) {
          $this->processExamVaardigheid();
        }
      }
    }
  }

  private $OPGAVE = 'Opgave';
  private $NR = 'Vraag nr.';
  private $VRAAGTYPEN = 'Vraagtypen';
  private $DOMEINEN = 'Domeinen';
  private $TREFWOORDEN = 'Trefwoorden';
  private $HIGHLIGHTS = 'Highlights';
  private $VAARDIGHEID = 'Vaardigheid';
  private $HOOFDSTUK_PREFIX = 'Hoofdstuk ';

  private $onbekend = [];
  private $opgave = null;
  private $nr = null;
  private $vraagtypen = null;
  private $domeinen = null;
  private $trefwoorden = null;
  private $highlights = null;
  private $vaardigheid = null;
  private $methodes = [];

  function initExamHeaders() {
    $this->clearExamHeaderInfo();
    for ($col = 1; $col <= 999; $col++) {
      $this->processExamHeaderInfo($col);
    }
    if (!$this->opgave) {
      $this->error("Examen {$this->year}-{$this->term} overgeslagen; kolom '{$this->OPGAVE}' ontbreekt.");
      return;
    }
    if (!$this->nr) {
      $this->error("Examen {$this->year}-{$this->term} overgeslagen; kolom '{$this->NR}' ontbreekt.");
      return;
    }
    if (count($this->onbekend)) {
      $kolommen = join("', '", $this->onbekend);
      $this->warn("Examen {$this->year}-{$this->term} heeft onbekende kolommen: '{$kolommen}'.");
    }
    if (count($this->methodes)) {
      // fixme: validation?
    }
    return true;
  }

  function clearExamHeaderInfo() {
    $this->opgave = null;
    $this->nr = null;
    $this->vraagtypen = null;
    $this->domeinen = null;
    $this->trefwoorden = null;
    $this->highlights = null;
    $this->vaardigheid = null;
    $this->methodes = [];
    $this->onbekend = [];
  }

  function processExamHeaderInfo($col) {
    $value = $this->getValue($col, 1);
    if ($value === $this->OPGAVE) {
      $this->opgave = $col;
    } else if ($value === $this->NR) {
      $this->nr = $col;
    } else if ($value === $this->VRAAGTYPEN) {
      $this->vraagtypen = $col;
    } else if ($value === $this->DOMEINEN) {
      $this->domeinen = $col;
    } else if ($value === $this->TREFWOORDEN) {
      $this->trefwoorden = $col;
    } else if ($value === $this->HIGHLIGHTS) {
      $this->highlights = $col;
    } else if ($value === $this->VAARDIGHEID) {
      $this->vaardigheid = $col;
    } else if ($value && str_starts_with($value, $this->HOOFDSTUK_PREFIX)) {
      $methode = str_replace($this->HOOFDSTUK_PREFIX, '', $value);
      array_push($this->methodes, $methode);
    } else if ($value) {
      array_push($this->onbekend, $value);
    }
  }

  function initExamQuestions() {
    // fixme
    $this->questions = [];
    $questions = $this->queryQuestions();
    foreach($questions as $question) {
      $this->questions[$question->number] = $question;
    }
    $opgave = null;
    $errors = 0;
    $this->vragen = [];
    for($row = 2; $row <= 999; ++$row) {
      $nr = $this->getValue($this->nr, $row);
      if ($nr) {
        $n = intval($nr);
        if (array_key_exists($n, $this->questions)) {
          $question = $this->questions[$n];
          $question->row = $row;
          $opgave = $this->getValue($this->opgave, $row, $opgave);
          $d = levenshtein($opgave, $question->name);
          if ($d === 0) {
            $this->vragen[] = $question;
          } else if ($d <= 3) {
            $posities = $d === 1 ? 'positie' : 'posities';
            $this->warn("Vraag {$n}; naam opgave '{$opgave}' in examen {$this->year}-{$this->term} wijkt af van verwachtte waarde '{$question->name}'.");
            $this->vragen[] = $question;
          } else {
            $this->error("Vraag {$n} overgeslagen; naam opgave '{$opgave}' in examen {$this->year}-{$this->term} wijkt af van verwachtte waarde '{$question->name}'.");
          }
        } else {
          $this->error("Vraag {$n} van examen {$this->year}-{$this->term} niet gevonden.");
        }
      }
    }
    foreach ($this->questions as $question) {
      if (!$question->row) {
        $this->error("Examen {$this->year}-{$this->term} mist vraag {$question->number}.");
      }
    }
    return count($this->vragen) > 0;
  }

  function queryQuestions() {
    return DB::select("
      SELECT
        questions.id,
        number,
        topic_id,
        name
      FROM
        questions,
        topics
      WHERE
        topic_id = topics.id AND
        exam_id = ?
    ", [
      $this->exam->id
    ]);
  }

  function processExamVraagtypen() {
    foreach($this->vragen as $vraag) {
      $vraagtypen = $this->getValue($this->vraagtypen, $vraag->row);
      $this->info($vraagtypen);
    }
  }

  function processExamDomeinen() {
    $this->info('domeinen');
  }

  function processExamTrefwoorden() {
    $this->info('trefwoorden');
  }

  function processExamHighlights() {
    $this->info('highlights');
  }

  function processExamVaardigheid() {
    $this->info('vaardigheid');
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
