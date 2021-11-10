<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:fix:scores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix scores';

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
      $scores = DB::select("
        SELECT DISTINCT
          question_id
        FROM scores
      ");
      foreach($scores as $s) {
        $question_id = $s->question_id;
        $this->info("question $question_id");
        $exams = DB::select("
          SELECT
            stream_id
          FROM questions, topics, exams
          WHERE topic_id = topics.id
            AND exam_id = exams.id
            AND questions.id = ?
        ", [ $question_id ]);
        foreach($exams as $e) {
          $stream_id = $e->stream_id;
          $this->info("stream $stream_id");
          DB::update("
            UPDATE scores
            SET stream_id = ?,
                is_newest = 0
            WHERE question_id = ?
          ", [ $stream_id, $question_id ]);
        }
      }
      $last = DB::select("
        SELECT
          user_id,
          question_id,
          MAX(id) AS max_id
        FROM scores
        GROUP BY user_id, question_id
      ");
      foreach($last as $l) {
        $max_id = $l->max_id;
        $this->info("stream $max_id");
        DB::update("
          UPDATE scores
          SET is_newest = 1
          WHERE id = ?
        ", [ $max_id ]);
      }
    }
}
