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


class AddNewton4 extends Command
{
    private $question;
    private $topic;
    private $exam;
    private $stream_id;

    protected $signature = 'ef:add:newton4';
    protected $description = 'Creates natuurkunde chapters';

    private function createMethode($name)
    {
      $this->info($name);
      $this->methode = Methodology::create([
        'stream_id' => $this->stream_id,
        'name' => $name
      ]);
    }

    private function createBook($name)
    {
      $this->info($name);
      $this->book = Chapter::create([
        'stream_id' => $this->stream_id,
        'methodology_id' => $this->methode->id,
        'name' => $name
      ]);
    }

    private function createChapter($name, $title)
    {
      $this->info($name.' '.$title);
      $this->chapter = Chapter::create([
        'stream_id' => $this->stream_id,
        'methodology_id' => $this->methode->id,
        'chapter_id' => $this->book->id,
        'name' => $name,
        'title' => $title
      ]);
    }

    public function handle() {

      $this->stream_id = 5;

      // Newton

      $this->createMethode('Newton 4 ed.');

      // Natuurkunde havo

      $this->stream_id = 5;
      $this->createBook('4 havo');
      $this->createChapter('H1', 'Elektriciteit');
      $this->createChapter('H2', 'Sport en verkeer');
      $this->createChapter('H3', 'Materialen');
      $this->createChapter('H4', 'Sport en verkeer');
      $this->createChapter('H5', 'Straling en gezondheid');
      $this->createChapter('H6', 'Vaardigheden');
      $this->createBook('5 havo');
      $this->createChapter('H7', 'Muziek en telecommunicatie');
      $this->createChapter('H8', 'Sport en verkeer');
      $this->createChapter('H9', 'Zonnestelsel en heelal');
      $this->createChapter('H10', 'Vaardigheden');

      // Natuurkunde vwo

      $this->stream_id = 6;
    }
}
