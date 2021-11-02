<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Level;
use App\Models\Topic;
use App\Models\Stream;
use App\Models\Course;
use App\Models\Domain;
use App\Models\Methodology;
use App\Models\Chapter;
use App\Models\Question;
use App\Models\QuestionType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MetaDataImport as ImportsMetaDataImport;


class ImportGR12 extends Command
{
    private $question;
    private $topic;
    private $exam;
    private $stream;

    protected $signature = 'ef:import:gr12 {file}';
    protected $description = 'Clears and imports meta data based on an Excel sheet';

    private function askChoice($question, $options)
    {
        $count = count($options);

        if ($count < 1) {
            $this->info($question);
            die("FOUT: Geen opties\n");
        }

        $n = 0;
        foreach($options as $option) {
            $choices[++$n] = $option;
        }

        if ($count === 1) {
            $this->info("$question -> $option");
            return $choices[1];
        }

        $choice = $this->choice($question, $choices);
        $option = array_search($choice, $options);

        return $option;
    }

    private function selectCourse()
    {
        $courses = Course::all()->mapWithKeys(
            fn ($row) => [$row->id => $row->name]
        )->toArray();

        $this->course_id = $this->askChoice("Vak?", $courses);
    }

    private function selectLevel()
    {
        $levels = Level::all()->mapWithKeys(
            fn ($row) => [$row->id => $row->name]
        )->toArray();

        $this->level_id = $this->askChoice("Niveau?", $levels);
    }

    private function getChapters($methodology_id)
    {
        $chapters = $this->stream->chapters
            ->whereNull('chapter_id')
            ->where('methodology_id', $methodology_id);

        return $chapters;
    }

    private function getChoices($chapters)
    {
        $options = [];
        foreach($chapters as $row) {
            $name = $row->name;
            $title = $row->title;
            $options[$row->id] = "$name ($title)";
        }
        return $options;
    }

    private function selectGR12Chapter()
    {
        $m = "Getal & Ruimte 12 ed.";
        $this->methode = Methodology::firstWhere('name', $m);

        if (!$this->methode) {
          $create = $this->askChoice("Geen methode \"$m\" gevonden. Aanmaken?", [1 => 'Ja', 0 => 'Nee' ]);
          if ($create) {
            $this->createGR12();
          } else {
            die("Script afgebroken.\n");
          }
        }

        // $chapters = $this->getChapters(3);
        // $count = count($chapters);

        // $choices = $this->getChoices($chapters);

        //$this->gr12_exam_chapter_id = $this->askChoice("Getal & Ruimte examenhoofdstuk?", $choices);
    }

    private function getStream()
    {
        $this->stream = Stream::query()
           ->where('level_id', $this->level_id)
           ->where('course_id', $this->course_id)
           ->first();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $file = $this->argument('file');

        $this->selectCourse();
        $this->selectLevel();

        $this->getStream();
        $this->selectGR12Chapter();

        $this->processFile($file);
    }

    private function processFile($file)
    {
        $collection = Excel::toCollection(new ImportsMetaDataImport, $file);
        $collection->each(function ($row) {
            $row->each(function ($item) {
                $this->processRow($item);
            });
        });
    }

    private function warning($message)
    {
        $this->info("LET OP: $message");
        if ($this->topic && $this->question) {
          $topic = $this->topic->name;
          $year = $this->topic->exam->year;
          $term = $this->topic->exam->term;
          $number = $this->question->number;
          $this->info("        $topic, Vraag $number ($year-$term)");
        }
    }

    private function getTopics($opgave)
    {
        $topics = Topic::query()
            ->where('name', $opgave)
            ->whereHas('exam', fn($q) => $q->where('stream_id', $this->stream->id))
            ->get();

        return $topics;
    }

    private function verbose_info($message)
    {
        if ($this->option('verbose')) {
            $this->info($message);
        }
    }

    private function similar($opgave)
    {
        $MATCH = 10;
        if (strlen($opgave) < $MATCH) {
            return;
        }

        $begin = substr($opgave, 0, $MATCH);
        $end = substr($opgave, -$MATCH, $MATCH);
        $topics = DB::select("
          select
            courses.name as vak,
            levels.name as niveau,
            exams.year as jaar,
            exams.term as tijdvak,
            topics.name as name,
            topics.id as id
          from topics, exams, streams, courses, levels
          where (topics.name like ? or topics.name like ?)
            and topics.exam_id = exams.id
            and exams.stream_id = streams.id
            and streams.course_id = courses.id
            and streams.level_id = levels.id
          order by abs(length(topics.name) - ?)
        ", [
          "$begin%",
          "%$end",
          strlen($opgave)
        ]);
        foreach($topics as $topic) {
          $vak = $topic->vak;
          $niveau = $topic->niveau;
          $jaar = $topic->jaar;
          $tijdvak = $topic->tijdvak;
          $id = $topic->id;
          $similar = $topic->name;
          $dist = levenshtein($opgave, $similar);
          $this->info("  Mogelijke match: \"$similar\" ±$dist ($vak $niveau, $jaar-$tijdvak)");
        }
    }

    private function processTopicField($opgave)
    {
        if (is_null($opgave)) {
            return;
        }
        
        $this->opgave = $opgave;

        $topics = $this->getTopics($opgave);
        $count = count($topics);

        if ($count < 1) {
            $this->topic = null;
            $this->warning("Opgave \"$opgave\" niet gevonden");
            $this->similar($opgave);
            return;
        }

        if ($count > 1) {
            $this->topic = null;
            $this->warning("Opgave \"$opgave\" ambigu ($count voorkomens)");
            foreach($topics as $topic) {
                $status = $topic->exam->status;
                $course = $topic->exam->stream->course->name;
                $level = $topic->exam->stream->level->name;
                $exam = $topic->exam->year.'-'.$topic->exam->term;
                $this->info("  In examen: ($course $level, $exam)");
            }
            return;
        }

        $this->topic = $topics[0];
        $this->topic->load([ 'exam' ]);

        $exam = $this->topic->exam->year.'-'.$this->topic->exam->term;
        $status = $this->topic->exam->status;
        $course = $this->stream->course->name;
        $level = $this->stream->level->name;

        if ($status === 'published') {
            $this->info("Opgave \"$opgave\" ($course $level, $exam)");
        } else if ($status === 'concept') {
            $this->info("Opgave \"$opgave\" ($course $level, $exam) STATUS = $status");
        }

        $this->verbose_info('');
    }

    private function getQuestions($vraag_nr)
    {
        $questions = $this->topic->questions
            ->where('number', $vraag_nr);
 
        return $questions;
    }

    private function processQuestionField($vraag_nr)
    {
        $this->question = null;
        $this->vraag_nr = $vraag_nr;

        if (is_null($vraag_nr)) {
            return;
        }

        if (is_null($this->topic)) {
            //$this->warning("Vraag $vraag_nr wordt niet verwerkt");
            return;
        }

        $questions = $this->getQuestions($vraag_nr);
        $count = count($questions);

        if ($count < 1) {
            $this->warning("Vraag $vraag_nr niet gevonden");
            return;
        }

        if ($count > 1) {
            $this->warning("Vraag $vraag_nr ambigu ($count voorkomens)");
            return;
        }

        $status = $this->topic->exam->status;
        $STATUS = ['published', 'concept'];

        if (in_array($status, $STATUS)) {
            foreach ($questions as $question) {
                $this->question = $question;
            }
            $this->info("Vraag $vraag_nr");
            $this->verbose_info("Vraag $vraag_nr");
        } else {
            $this->info("Genegeerd: Vraag $vraag_nr ($status)");
        }
    }

    private function processTopicQuestion()
    {
        if ($this->question) {
            return;
        }

        $vraag_nr = $this->vraag_nr;
        $opgave = $this->opgave;
        $questions = Question::where('number', $vraag_nr)
            ->with('topic', fn($q) => $q->where('name', $opgave))
            ->get();

        $count = count($questions);
        if ($count === 1) {
            $question = $questions->first();
            $topic = $question->topic;

            $exam = $topic->exam->year.'-'.$this->topic->exam->term;
            $status = $topic->exam->status;
            $course = $this->stream->course->name;
            $level = $this->stream->level->name;

            if ($status === 'published') {
                $this->info("Opgave \"$opgave\", vraag $vraag_nr ($course $level, $exam)");
            } else if ($status === 'concept') {
                $this->info("Opgave \"$opgave\", vraag $vraag_nr ($course $level, $exam) STATUS = $status");
            }

            $this->question = $question;
            $this->topic = $topic;
        }
    }

    public function processRow($row)
    {
        try {
            $opgave = $row['opgave'];
            $vraag_nr = $row['vraag_nr'];
        } catch(\Exception $err) {
            $this->verbose_info($err);
            return;
        }

        $this->processTopicField($opgave);
        $this->processQuestionField($vraag_nr);
        $this->processTopicQuestion();

        if ($this->question) {
            $this->processChapters($row);
        }
    }

    private function processChapter(&$sync, $chapters, $name_or_title)
    {
        $count = count($chapters);
        if ($count !== 1) {
            $this->warning("Afwijkend aantal voorkomens gevonden voor Hoofdstuk \"$name_or_title\" ($count/1)");
        }
        foreach($chapters as $chapter) {
            $id = $chapter->id;
            $name = $chapter->name;
            $title = $chapter->title;
            if (is_null($title)) {
                $this->verbose_info("Hoofdstuk#$id $name");
            } else {
                $this->verbose_info("Hoofdstuk#$id $name \"$title\"");
            }
            $sync[] = $chapter->id;
        }
    }

    private function processMainChapter(&$sync, $str)
    {
      foreach(explode("\n", trim($str)) as $line) {
        $stream_id = $this->stream->id;
        $words = explode(' ', $line);
        $name = array_pop($words);
        $part = join(' ', $words);

        $chapters = DB::select("
          select
            c.id,
            c.name,
            c.title
          from
            chapters p,
            chapters c
          where
            p.chapter_id is null
           and
            c.chapter_id = p.id
           and
            c.stream_id = ?
           and
            p.methodology_id = ?
           and
            p.name = ?
           and
            c.name = ?
        ", [ $this->stream->id, $this->methode->id, $part, $name ]);

        if (count($chapters) === 0) {
          $this->warning("Hoofdstuk '$part $name' niet gevonden (c.ctream_id={$this->stream->id} p.methodology_id={$this->methode->id} p.name={$part} c.name={$name}).");
        } else {
          $this->info("$part $name ({$this->methode->name})");
        }
        $this->processChapter($sync, $chapters, "$part $name ({$this->methode->name})");
      }
    }

    public function processChapters($row)
    {
        $sync = [];

        // Getal & Ruimte
        $this->processMainChapter($sync, $row['hoofdstuk_gr_ed_12']);

        $question_id = $this->question->id;
        foreach($sync as $chapter_id) {
      try {
          DB::insert("
            insert into question_chapter (question_id, chapter_id) values(?, ?)
          ", [ $question_id, $chapter_id ]);
      } catch (\ErrorException $err) {
$this->info('Ooops');
      }
        }

        //$this->question->chapters()->sync($sync);
    }

    private function createMethode($stream_id, $name)
    {
      $this->methode = Methodology::create([
        'stream_id' => $stream_id,
        'name' => $name
      ]);
    }

    private function createBook($stream_id, $name)
    {
      $this->book = Chapter::create([
        'stream_id' => $stream_id,
        'methodology_id' => $this->methode->id,
        'name' => $name
      ]);
    }

    private function createChapter($stream_id, $name, $title)
    {
      $this->chapter = Chapter::create([
        'stream_id' => $stream_id,
        'methodology_id' => $this->methode->id,
        'chapter_id' => $this->book->id,
        'name' => $name,
        'title' => $title
      ]);
    }

    private function createGR12()
    {
      $this->createMethode(1, 'Getal & Ruimte 12 ed.');
      $this->createBook(1, 'Deel 1');
      $this->createChapter(1, 'H1', 'Tabellen en grafieken');
      $this->createChapter(1, 'H2', 'De statistische cyclus');
      $this->createChapter(1, 'H3', 'Lineaire verbanden');
      $this->createChapter(1, 'H4', 'Handig tellen');
      $this->createBook(1, 'Deel 2');
      $this->createChapter(1, 'H5', 'Veranderingen');
      $this->createChapter(1, 'H6', 'Rekenregels en formules');
      $this->createChapter(1, 'H7', 'Statistiek en beslissingen');
      $this->createChapter(1, 'H8', 'Statistiek met de computer');
      $this->createBook(1, 'Deel 3');
      $this->createChapter(1, 'H9', 'Exponentiële verbanden');
      $this->createChapter(1, 'H10', 'Statistiek gebruiken');
      $this->createChapter(1, 'H11', 'Formules en variabelen');
      $this->createBook(1, 'Deel 4');
      $this->createChapter(1, '12.1', 'Algemene vaardigheden');
      $this->createChapter(1, '12.2', 'Lineaire verbanden');
      $this->createChapter(1, '12.3', 'Exponentiële verbanden');
      $this->createChapter(1, '12.4', 'Werken met formules');
      $this->createChapter(1, '12.5', 'Statistiek');
      $this->createChapter(1, '12.6', 'Onderzoeksopgaven');
    }
}
