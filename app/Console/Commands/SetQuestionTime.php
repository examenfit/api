<?php

namespace App\Console\Commands;

use App\Models\Exam;
use Illuminate\Console\Command;

class SetQuestionTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:question:time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set question time in minutes';

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
        Exam::with('topics.questions')->get()->each(function ($exam) {
            $totalPoints = $exam->topics->pluck('questions')->collapse()->sum('points');
            $minutes = $exam->stream->time_in_minutes ?: 5;
            $totalMinutes = $exam->stream->total_time_in_minutes ?: 180;
            $stream = $exam->stream->course->name.' '.$exam->stream->level->name;
            $term = $exam->year.'-'.$exam->term;
            $exam->topics->pluck('questions')->collapse()->each(function ($question) use ($stream, $term, $minutes, $totalMinutes, $totalPoints) {
                $t = round($question->points * $totalMinutes / $totalPoints);
                if ($t < 1) {
                  $t = 1;
                } else if ($t < $minutes) {
                  $t = round($t);
                } else {
                  $t = $minutes*round($t/$minutes);
                }
                $this->info($stream.' '.$term.' #'.$question->number.': '.$t.' min.');
                $question->update([
                    'time_in_minutes' => $t
                ]);
            });
        });


        return 0;
    }
}
