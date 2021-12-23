<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ImportOefensets extends Command {

  protected $signature = 'ef:import:oefensets {--dir=../meta} {--file=} {--sheet=oefenreeksen} {vak} {niveau}';
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
    $this->fix();
  }

  function init() {
    mb_internal_encoding('UTF-8');
    $this->initVak();
    $this->initNiveau();
    $this->initStream();
    $this->initFile();
    $this->initXlsx();
  }

  function initVak() {
    $vak = $this->argument('vak');
    if (array_key_exists($vak, ImportOefensets::COURSE)) {
      $this->vak = $vak;
    } else {
      die("Vak {$vak} niet gevonden.\n\n");
    }
  }

  function initNiveau() {
    $niveau = $this->argument('niveau');
    if (array_key_exists($niveau, ImportOefensets::LEVEL)) {
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
    return DB::select(ImportOefensets::QUERY_STREAMS, [
      ImportOefensets::COURSE[$this->vak],
      ImportOefensets::LEVEL[$this->niveau]
    ]);
  }

  function initFile() {
    if ($this->option('file')) {
      $this->file = $this->option('file');
    } else {
      $dir = $this->option('dir');
      $this->file = "{$dir}/{$this->vak}-{$this->niveau}-oefensets.xlsx"; 
    }
  }

  function initXlsx() {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
    $xlsx = $reader->load($this->file);
    $this->title = $this->option('sheet');
    $this->sheets = $xlsx->getAllSheets();
  }

  function process() {
    foreach($this->sheets as $key => $sheet) {
      $this->sheet = $sheet;
      $title = mb_strtolower($sheet->getTitle());
      if ($title === $this->title) {
        $this->import($sheet);
        return;
      }
    }
    $this->error('!! Werkblad "'.$this->title.'" niet gevonden.');
  }

  const DELETE_ANNOTATIONS = "
    DELETE FROM
      annotations
    WHERE
      stream_id = ?
  ";
  
  function deleteAnnotations() {
    DB::delete(ImportOefensets::DELETE_ANNOTATIONS, [ $this->stream->id ]);
    $vak = $this->vak;
    $niveau = $this->niveau;
    $this->warn("!! Metadata voor $vak $niveau gewist");
  }

  const QUERY_ONDERWERP = "
    SELECT
      *
    FROM
      annotations
    WHERE
      stream_id = ? AND
      name = ? AND
      type = 'oefenset'
    LIMIT 1
  ";

  function queryOnderwerp($onderwerp)
  {
    return DB::select(ImportOefensets::QUERY_ONDERWERP, [
      $this->stream->id,
      $onderwerp
    ]);
  }

  function getOnderwerp($onderwerp)
  {
    $r = $this->queryOnderwerp($onderwerp);
    return array_pop($r);
  }

  const INSERT_ONDERWERP = "
    INSERT INTO
      annotations
    SET
      stream_id = ?,
      name = ?,
      type = 'oefenset'
  ";

  function createOnderwerp($onderwerp)
  {
    $this->info("\nOnderwerp: $onderwerp");
    DB::insert(ImportOefensets::INSERT_ONDERWERP, [
      $this->stream->id,
      $onderwerp
    ]);
  }

  function initOnderwerp($onderwerp)
  {
    $row = $this->getOnderwerp($onderwerp);
    if (!$row) {
      $this->createOnderwerp($onderwerp);
      $row = $this->getOnderwerp($onderwerp);
    }
    if (!$row) {
      die("failed to get/create: $onderwerp");
    }
    $this->onderwerp = $row;
  }

  const QUERY_BASISVAARDIGHEID = "
    SELECT
      *
    FROM
      annotations
    WHERE
      stream_id = ? AND
      parent_id = ? AND
      name = ? AND
      type = 'basisvaardigheid'
  ";

  function queryBasisvaardigheid($basisvaardigheid)
  {
    return DB::select(ImportOefensets::QUERY_BASISVAARDIGHEID, [
      $this->stream->id,
      $this->onderwerp->id,
      $basisvaardigheid
    ]);
  }

  function getBasisvaardigheid($basisvaardigheid)
  {
    $r = $this->queryBasisvaardigheid($basisvaardigheid);
    return array_pop($r);
  }

  const INSERT_BASISVAARDIGHEID = "
    INSERT INTO
      annotations
    SET
      stream_id = ?,
      parent_id = ?,
      name = ?,
      type = 'basisvaardigheid'
  ";

  function createBasisvaardigheid($basisvaardigheid)
  {
    $this->info("Basisvaardigheid: $basisvaardigheid");
    return DB::insert(ImportOefensets::INSERT_BASISVAARDIGHEID, [
      $this->stream->id,
      $this->onderwerp->id,
      $basisvaardigheid
    ]);
  }

  function initBasisvaardigheid($basisvaardigheid)
  {
    $row = $this->getBasisvaardigheid($basisvaardigheid);
    if (!$row) {
      $this->createBasisvaardigheid($basisvaardigheid);
      $row = $this->getBasisvaardigheid($basisvaardigheid);
    }
    $this->basisvaardigheid = $row;
  }

  const QUERY_GECOMBINEERDE_OPGAVE = "
    SELECT
      *
    FROM
      annotations
    WHERE
      stream_id = ? AND
      parent_id = ? AND
      name = ? AND
      type = 'gecombineerde-opgaven'
  ";

  function queryGecombineerdeOpgave($opgave)
  {
    return DB::select(ImportOefensets::QUERY_GECOMBINEERDE_OPGAVE, [
      $this->stream->id,
      $this->onderwerp->id,
      $opgave
    ]);
  }

  function getGecombineerdeOpgave($opgave)
  {
    $r = $this->queryGecombineerdeOpgave($opgave);
    return array_pop($r);
  }

  const INSERT_GECOMBINEERDE_OPGAVE = "
    INSERT INTO
      annotations
    SET
      stream_id = ?,
      parent_id = ?,
      name = ?,
      type = 'gecombineerde-opgaven'
  ";

  function createGecombineerdeOpgave($opgave)
  {
    $this->info("GecombineerdeOpgave: $opgave");
    return DB::insert(ImportOefensets::INSERT_GECOMBINEERDE_OPGAVE, [
      $this->stream->id,
      $this->onderwerp->id,
      $opgave
    ]);
  }

  function initGecombineerdeOpgave($opgave)
  {
    $row = $this->getGecombineerdeOpgave($opgave);
    if (!$row) {
      $this->createGecombineerdeOpgave($opgave);
      $row = $this->getGecombineerdeOpgave($opgave);
    }
    $this->opgave = $row;
  }

  const QUERY_QUESTION = "
    SELECT
      questions.id,
      exams.status,
      exams.show_answers,
      topics.has_answers
    FROM
      questions,
      topics,
      exams
    WHERE
      year = ? AND
      term = ? AND
      number = ? AND
      stream_id = ? AND
      exam_id = exams.id AND
      exams.status IS NOT NULL AND
      exams.status <> 'frozen' AND
      topic_id = topics.id
  ";

  function queryQuestion($year, $term, $number)
  {
    return DB::select(ImportOefensets::QUERY_QUESTION, [
      $year,
      $term,
      $number,
      $this->stream->id
    ]);
  }

  function getQuestion($year, $term, $number)
  {
    $r = $this->queryQuestion($year, $term, $number);
    return array_pop($r);
  }

  function initQuestion($year, $term, $number)
  {
    $row = $this->getQuestion($year, $term, $number);
    if (!$row) {
      $this->warn("!! $year-$term #$number: Niet gevonde.");
    } else if ($row->status !== 'published') {
      $this->warn("!! $year-$term #$number: Ongeldige status: ".$row->status);
    } else if (!$row->show_answers) {
      $this->warn("!! $year-$term #$number: Antwoorden worden niet getoond");
    } else if (!$row->has_answers) {
      $this->warn("!! $year-$term #$number: Heeft geen antwoorden");
    } else {
      $this->question = $row;
      return true;
    }
  }

  const DELETE_QUESTION_ANNOTATIONS = "
    DELETE FROM
      question_annotation
    WHERE
      annotation_id = ?
  ";

  function deleteBasisvaardigheidAnnotations()
  {
    DB::delete(ImportOefensets::DELETE_QUESTION_ANNOTATIONS, [ $this->basisvaardigheid->id ]);
  }

  function deleteGecombineerdeOpgave()
  {
    DB::delete(ImportOefensets::DELETE_QUESTION_ANNOTATIONS, [ $this->opgave->id ]);
  }

  const INSERT_QUESTION_ANNOTATIONS = "
    INSERT INTO
      question_annotation
    SET
      annotation_id = ?,
      question_id = ?
  ";

  function createBasisvaardigheidAnnotation()
  {
    DB::insert(ImportOefensets::INSERT_QUESTION_ANNOTATIONS, [ $this->basisvaardigheid->id, $this->question->id ]);
  }

  function createGecombineerdeOpgaveAnnotation()
  {
    DB::insert(ImportOefensets::INSERT_QUESTION_ANNOTATIONS, [ $this->opgave->id, $this->question->id ]);
  }

  function validateSheet()
  {
    if (mb_strtolower($this->getValue(2,1)) === 'naam oefenreeks') {
      return true;
    }
    $this->error("!! 'Naam oefenreeks' expected in B1");
  }

  function import()
  {
    if (!$this->validateSheet()) {
      return; // quit
    }
    $this->deleteAnnotations();
    for ($row = 2; $row < 99; $row += 2) {
      $onderwerp = $this->getValue(2,$row);
      if ($onderwerp) {
        $this->initOnderwerp($onderwerp);
        $this->importOnderwerp($row);
      }
    }
  }

  function importOnderwerp($row)
  {
    for ($col = 3; $col < 99; $col += 1) {
      $used = $this->getValue($col, 1);
      $set = join(', ', explode("\n", trim($this->getValue($col, $row))));
      if ($set) {
        $type = mb_strtolower($this->getValue($col, 1));
        $vragen = explode("\n", trim($this->getValue($col, $row+1)));
        try {
          if (substr($type, 0, 5) === 'basis') {
            $this->initBasisvaardigheid($set);
            //$this->deleteBasisvaardigheidAnnotations();
            $this->importBasisvaardigheid($vragen);
          }
          if (substr($type, 0, 5) === 'doord') {
            //$this->initGecombineerdeOpgave($set);
            $this->initGecombineerdeOpgave('Gecombineerde opgaven');
            //$this->deleteGecombineerdeOpgave();
            $this->importGecombineerdeOpgave($vragen);
          }
        } catch(QueryException $e) {
          $this->warn('!! '.$row.','.$col);
          $this->warn('!! '.$e->getMessage());
        }
      }
    }
  }

  function importBasisvaardigheid($vragen)
  {
    foreach($vragen as $vraag) {
      $vraag = trim($vraag);
      $this->info('Vraag: "'.$vraag.'"');
      preg_match('/\\s*(\\d+)-([iI]+)[\\s-]+(\\d+)\\s*/', $vraag, $matches);
      if ($matches) {
        $year = +$matches[1];
        $term = strlen($matches[2]);
        $number = +$matches[3];
        if ($this->initQuestion($year, $term, $number)) {
          $this->createBasisvaardigheidAnnotation();
        }
      } else {
        $this->error("!! Ongeldig invoerformaat: \"$vraag\".");
      }
    }
  }

  function importGecombineerdeOpgave($vragen)
  {
    foreach($vragen as $vraag) {
      $vraag = trim($vraag);
      $this->info('Vraag: "'.$vraag.'"');
      preg_match('/(\\d+)-([iI]+)[\\s-]+(\\d+)/', $vraag, $matches);
      if ($matches) {
        $year = +$matches[1];
        $term = strlen($matches[2]);
        $number = +$matches[3];
        if ($this->initQuestion($year, $term, $number)) {
          $this->createGecombineerdeOpgaveAnnotation();
        }
      } else {
        $this->error("!! Ongeldig invoerformaat: \"$vraag\".");
      }
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

  function fix() {
    $isExplained = false;
    $annotations = DB::select("SELECT * FROM annotations WHERE parent_id IS NOT NULL");
    foreach($annotations as $annotation) {
      $questions = DB::select("SELECT * FROM question_annotation WHERE annotation_id = ?", [ $annotation->id ]);
      if (!count($questions)) {
        if (!$isExplained) {
          $this->info('Opschonen van entries waar geen vragen aan gekoppeld zijn:');
          $isExplained = true;
        }
        $this->info('#'.$annotation->id.' ('.$annotation->type.' '.$annotation->name.')');
        DB::delete("DELETE FROM annotations WHERE id = ?", [ $annotation->id ]);
      }
    }
  }
}
