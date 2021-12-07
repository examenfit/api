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


class AddNewton5 extends Command
{
    private $question;
    private $topic;
    private $exam;
    private $stream_id;

    protected $signature = 'ef:add:newton5';
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

      $this->createMethode('Newton 5 ed.');

      // Natuurkunde havo

      $this->stream_id = 5;
      $this->createBook('4 havo');
      $this->createBook('5 havo');

      // Natuurkunde vwo

      $this->stream_id = 6;
    }
}
