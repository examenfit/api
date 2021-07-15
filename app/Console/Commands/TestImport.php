<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MetaDataImport as ImportsMetaDataImport;

class TestImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:import:test {file}';

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

        $collection = Excel::toCollection(new ImportsTestImport, $file);
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
        $this->processMethodology($row['methode']);
        $this->processCourse($row['vak']);
        $this->processLevel($row['niveau']);
        $this->processPart($row['deel']);
        $this->processTest($row['hoofdstuk'], $row['titel']);
    }

    private function processMethodology($name)
    {
        if (!$name) {
            return;
        }

        $this->methodology = Methodology::where('name', $name)->first();
        if ($this->methodology) {
            $this->info("Methode \"$name\"");
        } else {
            $this->methodology = Methodology::create([
              'stream_id' => 1,
              'name' => $name
            ]);
            $this->info("Methode \"$name\" aangemaakt");
        }
    }

    private function processCourse($name)
    {
        if (!$name) {
            return;
        }

        $this->course = Course::where('name', $name)->first();
        if ($this->course) {
            $this->info("  Vak \"$name\"");
        } else {
            $this->fail("  Vak \"$name\" bestaat niet");
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
            $this->info("  Niveau \"$name\"");
        } else {
            $this->fail("  Niveau \"$name\" bestaat niet");
        }
        $this->stream = null;
    }
  
    private function processPart($name)
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
        $this->part = Test::query()
            ->where('name', $name)
            ->where('methodology_id', $this->methodology->id)
            ->where('stream_id', $this->stream->id)
            ->first();
        if ($this->part) {
            $this->info("    Deel \"$name\"");
        } else {
            $this->part = Test::create([
                'methodology_id' => $this->methodology->id,
                'stream_id' => $this->stream->id,
                'name' => $name
            ]);
            $this->info("    Deel \"$name\" aangemaakt");
        }
    }
    
    private function processTest($name, $title)
    {
        if (!$name) {
            return;
        }

        $titleStr = $title ? $title : '';
        $chapter = Test::query()
            ->where('name', $name)
            ->where('stream_id', $this->stream->id)
            ->where('methodology_id', $this->methodology->id)
            ->where('chapter_id', $this->part->id)
            ->first();
        if ($chapter) {
            $this->info("      Hoofdstuk \"$name $titleStr\"");
        } else {
            Test::create([
                'name' => $name,
                'title' => $title,
                'stream_id' => $this->stream->id,
                'methodology_id' => $this->methodology->id,
                'chapter_id' => $this->part->id
            ]);
            $this->info("      Hoofdstuk \"$name $titleStr\" aangemaakt");
        }
    }
}
