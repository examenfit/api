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


class AddNova extends Command
{
    private $question;
    private $topic;
    private $exam;
    private $stream_id;

    protected $signature = 'ef:add:nova';
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

      // Nova

      $this->createMethode('Nova');

      // Natuurkunde havo

      $this->stream_id = 5;
      $this->createBook('Deel 4A');
      $this->createChapter('H1', 'Beweging');
      $this->createChapter('H2', 'Elektriciteit');
      $this->createChapter('H3', 'Krachten');
      $this->createBook('Deel 4B');
      $this->createChapter('H4', 'Materialen');
      $this->createChapter('H5', 'Arbeid en energie');
      $this->createChapter('H6', 'Spiegels en lenzen');
      $this->createChapter('H7', 'Technische automatisering');
      $this->createBook('Deel 5');
      $this->createChapter('H8', 'Zuinig met energie');
      $this->createChapter('H9', 'Trillingen en golven');
      $this->createChapter('H10', 'Aarde en heelal');
      $this->createChapter('H11', 'Radioactiviteit');
      $this->createChapter('H12', 'Medische beeldvorming');
      $this->createChapter('H13', 'Aarde en klimaat');
      $this->createChapter('H14', 'Het menselijk lichaam');

      // Natuurkunde vwo

      $this->stream_id = 6;
      $this->createBook('Deel 4A');
      $this->createChapter('H1', 'Bewegingen beschrijven');
      $this->createChapter('H2', 'Kracht en beweging');
      $this->createChapter('H3', 'Energieomzettingen');
      $this->createBook('Deel 4B');
      $this->createChapter('H4', 'Elektrische systemen');
      $this->createChapter('H5', 'Biofysica: de natuurkunde van het leven');
      $this->createChapter('H6', 'Geofysica: de natuurkunde van de aarde');
      $this->createBook('Deel 5');
      $this->createChapter('H7', 'Trillingen en golven');
      $this->createChapter('H8', 'Elektrische en magnetische velden');
      $this->createChapter('H9', 'Elektromagnetische straling en materie');
      $this->createChapter('H10', 'Ioniserende straling');
      $this->createChapter('H11', 'Stof- en materiaaleigenschappen');
      $this->createBook('Deel 6');
      $this->createChapter('H12', 'Quantumwereld');
      $this->createChapter('H13', 'Gravitatie');
      $this->createChapter('H14', 'Natuurwetten en modellen');
      $this->createChapter('H15', 'Relativiteit');
      $this->createChapter('H16', 'Kern- en deeltjesprocessen');
    }
}
