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

            $exam->topics->pluck('questions')->collapse()->each(function ($question) use ($totalPoints) {
                $question->update([
                    'time_in_minutes' => round($question->points * (180 / $totalPoints)/5, 0)* 5
                ]);
            });
        });


        return 0;
    }
}
