<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateMethodologyToChapter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:migrateMethodologyToChapter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $items = DB::table('question_methodology')->get();

        $mapping = collect([
            "4 VWO A/C H5" => 19,
            "Deel 1 H1" => 1,
            "Deel 1 H2" => 2,
            "Deel 1 H3" => 3,
            // "Deel 1 H4" => WISSEN,
            "Deel 2 H6" => 4,
            "Deel 3 H10" => 6,
            "Deel 3 H8" => 5,
            "Deel 4 H12" => 7,
            "Deel 4 H13" => 8,
            "Deel 4 H14" => 9,
            // "Onderbouw" => WISSEN,
            "4 vwo A/C H1" => 16,
            "4 VWO A/C H2" => 17,
            // "4 vwo A/C H3" => WISSEN,
            "4 vwo A/C H4" => 18,
            "4 vwo A/C H6" => 20,
            "4 vwo A/C H8C" => 21,
            "5 VWO A H1" => 22,
            "5 vwo A H3" => 23,
            "5 VWO A H5" => 24,
            "5 vwo A H6" => 25,
            "5 vwo H5" => 24,
            "6 vwo A H3" => 27,
            "6 vwo A H4" => 28,
            "6 VWO A H5" => 29,
            "6 vwo A H6" => 30,
        ]);

        foreach ($items as $item) {
            if ($mapping->has($item->chapter)) {
                DB::table('question_chapter')->insert([
                    'question_id' => $item->question_id,
                    'chapter_id' => $mapping[$item->chapter],
                ]);
            }
        }
    }
}
