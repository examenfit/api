<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\AnswerSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SplitMultipleMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:splitMultipleMethods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Split multiple answer solutions';

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
        $record = AnswerSection::with('answer.question')
            ->where('correction', 'LIKE', '%methode%')
            ->first();

        while ($record) {

            $newAnswer = $record->answer->question->answers()->create([
                'type' => 'correction',
            ]);

            DB::table('answer_sections')
                ->where('answer_id', $record->answer_id)
                ->where('id', '>=', $record->id)
                ->update([
                    'answer_id' => $newAnswer->id,
                ]);

            $record->update([
                'correction' => trim(substr($record->correction, strpos($record->correction, "\n") + 1)),
            ]);

            $record = AnswerSection::with('answer.question')
                ->where('correction', 'LIKE', '%methode%')
                ->first();
        }
    }
}
