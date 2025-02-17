<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\Exam;
use App\Models\Topic;
use App\Models\Query;
use App\Models\Annotation;

class UpdateMetaData extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ef:update:metadata';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Update annotations to include all exams';

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
    $exams = Exam::query()
      ->get();
    foreach($exams as $exam) {
      $exam->load([ 'stream', 'topics.questions' ]);
      if ($this->skipExam($exam)) continue;
      foreach ($exam->topics as $topic) {
        if ($this->skipTopic($exam, $topic)) continue;
        foreach ($topic->questions as $question) {
          try {
            $this->info("processing {$exam->stream->slug} {$exam->year}-{$exam->term} {$topic->name} {$question->number}");
            $exam_annotation = Annotation::firstOrCreate([
              'name' => "{$exam->year} {$exam->term}e tijdvak",
              'stream_id' => $exam->stream_id,
              'type' => 'examen',
            ], [
              'position' => 999999 - 150*$exam->year + 50*$exam->term,
            ]);
            $topic_annotation = Annotation::firstOrCreate([
              'name' => $topic->name,
              'parent_id' => $exam_annotation->id,
              'stream_id' => $exam->stream_id,
              'type' => 'opgave',
            ], [
              'position' => 999999 - 150*$exam->year + 50*$exam->term + $question->number,
            ]);
            $topic_annotation->questions()->syncWithoutDetaching([ $question->id ]);
          }
          catch (\Exception $error) {
            $this->info("ingored: {$error->getMessage()}");
          }
        }
      }
    }
  }

  private function skipExam($exam) {
    if ($exam->status !== 'published') {
      $this->info("skipping exam {$exam->stream->slug} {$exam->year}-{$exam->term}: status not published");
      return TRUE;
    }
    if ($exam->show_answers !== 1) {
      $this->info("skipping exam {$exam->stream->slug} {$exam->year}-{$exam->term}: show_answers not true");
      return TRUE;
    }
  }

  private function skipTopic($exam, $topic) {
    if ($topic->has_answers !== 1) {
      $this->info("skipping topic {$exam->stream->slug} {$exam->year}-{$exam->term} {$topic->name}: has_answers not true");
      return TRUE;
    }
  }

}
