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


class AddOveral4 extends Command
{
    private $question;
    private $topic;
    private $exam;
    private $stream_id;

    protected $signature = 'ef:add:overal4';
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

      // Overal

      $this->createMethode('Overal Natuurkunde 4 ed.');

      // Natuurkunde havo

      $this->stream_id = 5;

      // Natuurkunde vwo

      $this->stream_id = 6;
      $this->createBook('4 vwo');
/*
      $this->createChapter('H1', '');
      $this->createChapter('H2', '');
      $this->createChapter('H3', '');
      $this->createChapter('H4', '');
      $this->createChapter('H5', '');
      $this->createChapter('H6', '');
*/
      $this->createBook('5 vwo');
      $this->createChapter('H7', 'Stoffen en materialen');
      $this->createChapter('H8', 'Hemelmechanica');
      $this->createChapter('H9', 'Golven');
      $this->createChapter('H10', 'Medische beeldvorming');
      $this->createChapter('H11', 'Astrofysica');
      $this->createBook('6 vwo');
      $this->createChapter('H12', 'Elektrische velden');
      $this->createChapter('H13', 'Magnetische velden');
      $this->createChapter('H14', 'Mechanica in samenhang');
      $this->createChapter('H15', 'Quantumwereld');
      $this->createChapter('H16', 'Finale');


    }
}
