<?php

namespace App\Console\Commands;

use App\Models\Level;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CalculateComplexity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:calculateComplexity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automagically calculate the complexity of topics and questions based of the proportion threshold values';

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
        if ($this->confirm(
            "Do you also want to run ef:cache:topics to calculate the latest weighted proportion values for topics?"
        )) {
            Artisan::call('ef:cache:topics');
        }

        // Calculate for topics
        Topic::with('exam', 'questions')->whereHas('exam', function ($query) {

            // Only questions from the first term have a proportion value
            return $query->where('term', 1);
        })->get()->each(function ($topic) {

            $stream = $topic->exam->stream;

            if ($stream->proportion_threshold_low && $stream->proportion_threshold_high) {
                $complexity = $this->complexity($stream, $topic->cache['weightedProportionValue']);

                if ($complexity !== $topic->complexity) {
                    $this->info(
                        "Change complexity of " . $topic->name .
                            " from " . $topic->complexity .
                            " to " . $complexity
                    );

                    $topic->update([
                        'complexity' => $complexity,
                    ]);
                }

                // Calculate for questions
                $topic->questions->each(function ($question) use ($stream, $topic) {
                    $complexity = $this->complexity($stream, $question->proportion_value);

                    if ($complexity !== $question->complexity) {
                        $this->info(
                            "Change complexity of question #" . $question->number .
                                " of topic " . $topic->name .
                                " from " . $question->complexity .
                                " to " . $complexity
                        );

                        $question->update([
                            'complexity' => $complexity,
                        ]);
                    }
                });
            }
        });
    }

    // https://trello.com/c/kLj6bySG/140-complexiteit-automatisch-berekenen
    public function complexity($stream, $proportionValue)
    {
        if (!$proportionValue) {
            return null;
        } elseif ($proportionValue <= $stream->proportion_threshold_low) {
            return 'high';
        } elseif ($proportionValue > $stream->proportion_threshold_high) {
            return 'low';
        } else {
            return 'average';
        }
    }
}
