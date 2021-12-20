<?php

namespace App\Console\Commands;

use App\Models\Methodology;
use App\Models\Chapter;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddWisVMBO extends Command
{
    protected $signature = 'ef:add:wiskunde-vmbo';
    protected $description = 'Creates wiskunde vmbo chapters';

    private function useStream($course, $level)
    {
      $r = DB::select("
        SELECT streams.*
        FROM streams, courses, levels
        WHERE courses.id = course_id AND
              courses.name = ? AND
              levels.id = level_id AND
              levels.name = ?
      ", [ $course, $level ]);
      $this->stream = $r[0];
    }

    private function getMethode($name)
    {
      $this->methode = Methodology::firstWhere('name', $name);
      return $this->methode;
    }

    private function createMethode($name)
    {
      $this->info($name);
      $this->methode = Methodology::create([
        'stream_id' => $this->stream->id,
        'name' => $name
      ]);
      return $this->methode;
    }

    private function useMethode($name)
    {
      if (!$this->getMethode($name)) { 
        $this->createMethode($name);
      }
    }

    private function createBook($name)
    {
      $this->info($name);
      $this->book = Chapter::create([
        'stream_id' => $this->stream->id,
        'methodology_id' => $this->methode->id,
        'name' => $name
      ]);
      return $this->book;
    }

    private function createChapter($name, $title)
    {
      $this->info($name.' '.$title);
      $this->chapter = Chapter::create([
        'stream_id' => $this->stream->id,
        'methodology_id' => $this->methode->id,
        'chapter_id' => $this->book->id,
        'name' => $name,
        'title' => $title,
        'import_id' => $this->book->name.' - '.$name.' '.$title
      ]);
      return $this->chapter;
    }

    public function handle() {

      $this->useStream('Wiskunde', 'Vmbo GT');
      //$this->useStream('Wiskunde', 'Vmbo GL en TL');

      $this->useMethode('Getal & Ruimte 12 ed.');

      $this->createBook('3 vmbo-kgt');
      $this->createChapter('H1', 'Procenten');
      $this->createChapter('H2', 'Meetkunde');
      $this->createChapter('H3', 'Formules en grafieken');
      $this->createChapter('H4', '?');
      $this->createChapter('H5', 'Goniometrie');
      $this->createChapter('H6', 'Verschillende verbanden');
      $this->createChapter('H7', 'Oppervlakte en inhoud');
      $this->createChapter('H8', 'Getallen');
      $this->createChapter('H9', 'Grafieken en vergelijkingen');
      $this->createChapter('H10', 'Goniometrie');

      $this->createBook('4 vmbo-kgt');
      $this->createChapter('H1', '?');
      $this->createChapter('H2', 'Verbanden');
      $this->createChapter('H3', 'Drie dimensies, afstanden en hoeken');
      $this->createChapter('H4', 'Grafieken en vergelijkingen');
      $this->createChapter('H5', 'Rekenen, meten en schatten');
      $this->createChapter('H6', 'Vlakke figuren');
      $this->createChapter('H7', 'Verbanden');
      $this->createChapter('H8', 'Ruimtemeetkunde');

      $this->useMethode('Moderne Wiskunde 12 ed.');

      $this->createBook('3A vmbo-gt');
      $this->createChapter('H1', 'Formules en grafieken');
      $this->createChapter('H2', 'Plaats en afstand');
      $this->createChapter('H3', 'Rekenen met formules');
      $this->createChapter('H4', 'Werken met aantallen');
      $this->createChapter('H5', 'Gelijkvormigheid');
      $this->createChapter('H6', '?');

      $this->createBook('3B vmbo-gt');
      $this->createChapter('H7', 'Vergelijkingen oplossen');
      $this->createChapter('H8', 'Hellingen en tangens');
      $this->createChapter('H9', 'Meten en redeneren');
      $this->createChapter('H10', 'Grafieken');
      $this->createChapter('H11', 'Oppervlakte en inhoud');
      $this->createChapter('H12', 'Grafen');

      $this->createBook('4A vmbo-gt');
      $this->createChapter('H1', 'Grafieken en vergelijkingen');
      $this->createChapter('H2', 'Vlakke Meetkunde');
      $this->createChapter('H3', '?');
      $this->createChapter('H4', 'Machtsverbanden');
      $this->createChapter('H5', 'Rekenen');

      $this->createBook('4B vmbo-gt');
      $this->createChapter('H6', 'Goniometrie');
      $this->createChapter('H7', 'Exponentiële formules');
      $this->createChapter('H8', 'Ruimtemeetkunde');
    }
}
