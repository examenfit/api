<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Level;
use App\Models\Stream;
use App\Models\QuestionType;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\QuestionTypeImport as ImportsQuestionTypeImport;

class QuestionTypeImport extends Command
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
    protected $signature = 'ef:import:questiontypes {file}';

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //$file = $this->ask("What is the path to the file?");
        $file = $this->argument('file');
        $this->processFile($file);
    }

    private function processFile($file)
    {
        $collection = Excel::toCollection(new ImportsQuestionTypeImport, $file);
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

    private function fail($message)
    {
        $this->fail("LET OP: $message");
    }

    private function processRow($row)
    {
        $this->processCourse($row['vak']);
        $this->processLevel($row['niveau']);
        $this->processQuestionType($row['vraagtype']);
    }

    private function processCourse($name)
    {
        if (!$name) {
            return;
        }

        $this->course = Course::where('name', $name)->first();
        if ($this->course) {
            $this->info("Vak \"$name\"");
        } else {
            $this->fail("Vak \"$name\" bestaat niet");
        }
        $this->stream = null;
    }

    private function processLevel($name)
    {
        if (!$name) {
            return;
        }

        $this->level = Level::where('name', $name)->first();
        if ($this->level) {
            $this->info("Niveau \"$name\"");
        } else {
            $this->fail("Niveau \"$name\" bestaat niet");
        }
        $this->stream = null;
    }
  
    private function processQuestionType($name)
    {
        if (!$name) {
            return;
        }

        if (!$this->stream) {
            $course = $this->course->name;
            $level = $this->level->name;
            $this->stream = Stream::query()
                ->where('course_id', $this->course->id)
                ->where('level_id', $this->level->id)
                ->first();
            if ($this->stream) {
                $this->info("  Stroom \"$course $level\"");
            } else {
                $this->fail("  Stroom \"$course $level\" bestaat niet");
            }
        }
        $chapter = QuestionType::query()
            ->where('name', $name)
            ->where('stream_id', $this->stream->id)
            ->first();
        if ($chapter) {
            $this->info("  Vraagtype \"$name\"");
        } else {
            QuestionType::create([
                'name' => $name,
                'course_id' => 1, // deprecated
                'stream_id' => $this->stream->id,
            ]);
            $this->info("  Vraagtype \"$name\" aangemaakt");
        }
    }
}
