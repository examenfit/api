<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Support\DocumentMarkup;

class FixAdminAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:generate:markup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Katex markup';

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
        $this->markup = new DocumentMarkup();
        $this->fixStreams();
        $this->fixTopics();
        $this->fixQuestions();
    }

    function fixTopics()
    {
        $rows = DB::select("
            SELECT id, introduction
            FROM topics
            WHERE introductionHtml = ''
        ");

        foreach ($rows as $row) {
          try {
            $id = $row->id;
            $introduction = $row->introduction;
            $introductionHtml = $this->markup->fix($introduction);
            DB::update("
              UPDATE topics
              SET introductionHtml = ?
              WHERE id = ?
            ", [ $introductionHtml, $id ]);
            print("\nTopic#{$id} {$introductionHtml}\n");
          } catch(\Exception $error) {
            print('ERROR: '.$error->getMessage());
          }
        }
    }

    function fixQuestions()
    {
        $rows = DB::select("
            SELECT id, introduction
            FROM questions
            WHERE introductionHtml = ''
        ");

        foreach ($rows as $row) {
          try {
            $id = $row->id;
            $introduction = $row->introduction;
            $introductionHtml = $this->markup->fix($introduction);
            DB::update("
              UPDATE questions
              SET introductionHtml = ?
              WHERE id = ?
            ", [ $introductionHtml, $id ]);
            print("\nQuestion#{$id} {$introductionHtml}\n");
          } catch(\Exception $error) {
            print('ERROR: '.$error->getMessage());
          }
        }

        $rows = DB::select("
            SELECT id, text
            FROM questions
            WHERE textHtml = ''
        ");

        foreach ($rows as $row) {
          try {
            $id = $row->id;
            $text = $row->text;
            $textHtml = $this->markup->fix($text);
            DB::update("
              UPDATE questions
              SET textHtml = ?
              WHERE id = ?
            ", [ $textHtml, $id ]);
            print("\nQuestion#{$id} {$textHtml}\n");
          } catch(\Exception $error) {
            print('ERROR: '.$error->getMessage());
          }
        }
    }

    function fixStreams()
    {
        $rows = DB::select("
            SELECT id, formuleblad
            FROM streams
            WHERE formulebladHtml = ''
        ");

        foreach ($rows as $row) {
          try {
            $id = $row->id;
            $formuleblad = $row->formuleblad;
            $formulebladHtml = $this->markup->fix($formuleblad);
            DB::update("
              UPDATE streams
              SET formulebladHtml = ?
              WHERE id = ?
            ", [ $formulebladHtml, $id ]);
            print("\nStream#{$id} {$formulebladHtml}\n");
          } catch(\Exception $error) {
            print('ERROR: '.$error->getMessage());
          }
        }
    }

    function fixAnswerSections()
    {
        // not used in CollectionsController::showCollectionQuestionsHtml
    }

}
