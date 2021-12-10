<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTopicFlags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:update:flags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Topic has_tips and has_answer flags';

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
        DB::update("
            UPDATE topics
            SET has_answers = 0
        ");
        DB::update("
            UPDATE topics
            SET has_answers = 1
            WHERE id IN (
                SELECT q.topic_id
                FROM questions q, answers a, answer_sections s
                WHERE a.question_id = q.id
                  AND a.id = s.answer_id
                  AND a.status = 'published'
                  AND s.text > ''
            )
        ");
    }
}
