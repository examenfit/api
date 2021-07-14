<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Level;
use App\Models\Topic;
use App\Models\Stream;
use App\Models\Course;
use App\Models\Domain;
use App\Models\Chapter;
use App\Models\Question;
use App\Models\QuestionType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MetaDataImport as ImportsMetaDataImport;

class MetaDataImport extends Command
{
    private $question;
    private $topic;
    private $exam;
    private $stream;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:import:metadata {file} {--chapters} {--purge-tags}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears and imports meta data based on an Excel sheet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function askChoice($question, $options)
    {
        $count = count($options);

        if ($count < 1) {
            $this->info($question);
            die('FOUT: Geen opties');
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

    private function selectGRChapter()
    {
        $chapters = $this->getChapters(1);
        $count = count($chapters);

        if ($count < 1) {
            die('Geen hoofdstukken voor "Getal & Ruimte" gevonden');
        }

        $choices = $this->getChoices($chapters);

        $this->gr_exam_chapter_id = $this->askChoice("Getal & Ruimte examenhoofdstuk?", $choices);
    }

    private function selectMWChapter()
    {
        $chapters = $this->getChapters(2);
        $count = count($chapters);

        if ($count < 1) {
            die('Geen hoofdstukken voor "Moderne Wiskunde" gevonden');
        }

        $choices = $this->getChoices($chapters);

        $this->mw_exam_chapter_id = $this->askChoice("Getal & Ruimte examenhoofdstuk?", $choices);
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
        //$file = $this->ask("What is the path to the file?");
        $file = $this->argument('file');

        $this->selectCourse();
        $this->selectLevel();

        $this->getStream();
        if ($this->option("purge-tags")) {
            Tag::where('stream_id', $this->stream->id)->delete();
        }

        if ($this->option('chapters')) {
          $this->selectGRChapter();
          $this->selectMWChapter();
        }

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

    private function similarity($a, $b)
    {
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
            $this->verbose_info("Vraag $vraag_nr");
        } else {
            //$this->info("Genegeerd: Vraag $vraag_nr");
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
        $this->processTopicField($row['opgave']);
        $this->processQuestionField($row['vraag_nr']);
        $this->processTopicQuestion();

        if ($this->question) {
            $this->processDomains($row['domeinen']);
            $this->processQuestionType($row['vraagtypen']);
            $this->processHighlights($row['highlights']);
            $this->processTags($row['trefwoorden']);
            if ($this->option('chapters')) {
              $this->processChapters($row);
            }
            $this->verbose_info('');
        }
    }


    private function processChapter(&$sync, $chapters, $title)
    {
        $count = count($chapters);
        if ($count !== 1) {
            $this->warning("Afwijkend aantal voorkomens gevonden voor Hoofdstuk \"$title\" ($count/1)");
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

    private function processMainChapter(&$sync, $text, $methodology_id, $exam_chapter_id)
    {
        $titles = explode("\n", $text);
        foreach ($titles as $title) {
            $stream_id = $this->stream->id;
            $this->info("stream=$stream_id, methodology=$methodology_id, title=$title");
            //$chapters = $this->stream->chapters
            $chapters = Chapter::where('stream_id', $this->stream->id)
                ->where('title', $title)
                ->where('methodology_id', $methodology_id)
                ->where('chapter_id', '!=', $exam_chapter_id)->get();
            $this->processChapter($sync, $chapters, $title);
        }
    }

    private function processExamChapter(&$sync, $text, $methodology_id, $exam_chapter_id)
    {
        $titles = explode("\n", $text);
        foreach ($titles as $title) {
            $chapters = $this->stream->chapters
                ->where('name', $title)
                ->where('methodology_id', $methodology_id)
                ->where('chapter_id', $exam_chapter_id);
            $this->processChapter($sync, $chapters, $title);
        }
    }

    public function processChapters($row)
    {
        $sync = [];

        // Getal & Ruimte
        $this->processMainChapter($sync, $row['hoofdstuktitel_gr'], 1, $this->gr_exam_chapter_id);
        $this->processExamChapter($sync, $row['examentraining_gr'], 1, $this->gr_exam_chapter_id);

        // Moderne Wiskunde
        $this->processMainChapter($sync, $row['hoofdstuktitel_mw'], 2, $this->mw_exam_chapter_id);
        $this->processExamChapter($sync, $row['examentraining_mw'], 2, $this->mw_exam_chapter_id);

        $this->question->chapters()->sync($sync);
    }

    private function getDomains($code, $index, $name)
    {
        $domains = Domain::query()
            ->where('stream_id', $this->stream->id)
            ->where('name', 'LIKE', "%($code$index)")
            ->get();

        $count = count($domains);
        if ($count === 1) {
            return $domains->first();
        }
        if ($count === 0) {
            if ($index) {
                $parent_id = $this->getDomains($code, '', $name)->id;
            } else {
                $parent_id = null;
            }
            $domain = Domain::create([
                'stream_id' => $this->stream->id,
                'name' => "$name ($code$index)",
                'parent_id' => $parent_id
            ]);
            return $domain;
        }

        $this->warning("Afwijkend aantal voorkomens gevonden voor Domein \"($code)\" ($count/1)");
        return [];
    }

    private function getDomain($value)
    {
        $matches = [];
        if (preg_match('/^([A-Z])([0-9]*)\\W+(.*)$/', $value, $matches)) {
            $code = $matches[1];
            $index = $matches[2];
            $name = $matches[3];
        } else {
            $this->warning("Ongeldig invoerpatroon voor Domein \"$value\"");
            return;
        }

        $domain = $this->getDomains($code, $index, $name);

        return $domain;
    }

    public function processDomains($text)
    {
        $sync = [];
        $values = explode("\n", $text);
        foreach($values as $value) {
            if ($domain = $this->getDomain($value)) {
               $id = $domain->id;
               $name = $domain->name;
               $this->verbose_info("Domein#$id $name");
               $sync[] = $id;
            }
        }
        $this->question->domains()->sync($sync);
    }

    public function processTags($text)
    {
        $tags = [];
        $names = array_filter(explode("\n", $text));

        foreach ($names as $name) {
            $tag = Tag::query()
              ->where('stream_id', $this->stream->id)
              ->where('name', $name)
              ->first();
            if (!$tag) {
                $tag = Tag::forceCreate([
                    'stream_id' => $this->stream->id,
                    'name' => $name,
                ]);
            }
            $id = $tag->id;
            $tags[] = $tag->id;
            $this->verbose_info("Trefwoord#$id \"$name\"");
        }

        $this->question->tags()->sync($tags);
    }

    public function processQuestionType($value)
    {
        if (is_null($value) || $value === "") {
            $this->warning("Vraagtype is leeg");
            return;
        }

        $type = QuestionType::query()
            ->where('stream_id', $this->stream->id)
            ->where('name', $value)
            ->first();

        if (!$type) {
            $type = QuestionType::create([
                'stream_id' => $this->stream->id,
                'course_id' => $this->course_id,
                'name' => $value,
            ]);
        }

        $this->question->update([ 'type_id' => $type->id, ]);
        $id = $type->id;
        $this->verbose_info("Vraagtype#$id \"$value\"");
    }

    public function processHighlights($value)
    {
        if (is_null($value) || $value === "") {
            $this->warning("Highlight is leeg");
            return;
        }

        $this->question->highlights()->delete();
        $highlight = $this->question->highlights()->create([ 'text' => $value, ]);

        $id = $highlight->id;
        $this->verbose_info("Highlight#$id \"$value\"");
    }
}
