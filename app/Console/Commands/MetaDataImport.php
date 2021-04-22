<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\Domain;
use App\Models\Chapter;
use App\Models\Question;
use App\Models\QuestionType;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MetaDataImport as ImportsMetaDataImport;

class MetaDataImport extends Command
{
    public $topic;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:import:metadata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears and imports meta data based on an Excel sheet';

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
        $collection = Excel::toCollection(new ImportsMetaDataImport, 'vwoa.xlsx');

        $collection->each(function ($row) {
            $row->each(function ($item) {
                $this->processRow($item);
            });
        });


        // $rows->each(function ($row) {
        //     dd($row);
        // });
        // //     $this->processRow($row);
        // // }
    }

    public function processRow($row)
    {
        if (!is_null($row['opgave'])) {
            $this->info('Opgave ophalen: ' . $row['opgave']);
            $this->topic = Topic::where('name', $row['opgave'])->firstOrFail();
        }

        if (!is_null($row['vraag_nr'])) {
            // dd($row);
            $this->info('Vraag ophalen: ' . $row['vraag_nr']);

            $question = Question::query()
                ->where('topic_id', $this->topic->id)
                ->where('number', $row['vraag_nr'])
                ->firstOrFail();

            $this->processChapters($question, $row);
            $this->processDomains($question, $row['domeinen']);
            $this->processTags($question, $row['trefwoorden']);
            $this->processQuestionType($question, $row['vraagtypen']);
            $this->processHighlights($question, $row['highlights']);
        }
    }

    public function processChapters($question, $row)
    {
        $chapters = [];

        // Getal & Ruimte
        $this->info('Hoofdstuk 1 ophalen: ' . $row['hoofdstuktitel_gr']);
        $process = explode("\n", $row['hoofdstuktitel_gr']);
        foreach ($process as $item) {
            $chapters[] = Chapter::where('methodology_id', 1)
                ->where('title', $item)
                ->firstOrFail()
                ->id;
        }

        $this->info('Hoofdstuk 2 ophalen: ' . $row['examentraining_gr']);
        $process = explode("\n", $row['examentraining_gr']);
        foreach ($process as $item) {
            $chapters[] = Chapter::where('methodology_id', 1)
                ->where('chapter_id', 10) // Examentraining
                ->where('name', $item)
                ->firstOrFail()
                ->id;
        }

        // Moderne wiskunde
        $this->info('Hoofdstuk 3 ophalen: "' . trim($row['hoofdstuktitel_mw']) . '"');
        $process = explode("\n", $row['hoofdstuktitel_mw']);
        foreach ($process as $item) {
            $chapters[] = Chapter::where('methodology_id', 2)
                ->where('title', trim($item))
                ->firstOrFail()
                ->id;
        }

        $this->info('Hoofdstuk 4 ophalen: ' . $row['examentraining_mw']);
        $process = explode("\n", $row['examentraining_mw']);
        foreach ($process as $item) {
            $chapters[] = Chapter::where('methodology_id', 2)
                ->where('chapter_id', 31) // Examentraining
                ->where('name', trim($item))
                ->firstOrFail()
                ->id;
        }

        $question->chapters()->sync($chapters);
    }

    public function processDomains($question, $values)
    {
        $domains = collect(explode("\n", $values))
            ->map(function ($domain) {
                $this->info('Domein splitten: ' . $domain);
                return explode(': ', $domain)[1];
            })
            ->map(function ($domain) {
                if (
                    $domain === "Exponentiële verbanden"
                    || $domain === "Exponentiële en logaritmische functie"
                ) {
                    $domain = "Exponentiële en logaritmische functies";
                }

                $this->info('Domein ophalen: ' . $domain);

                return Domain::where('name', 'LIKE', $domain . '%')
                    ->where(function ($query) {
                        $query->whereNotNull('parent_id')
                            ->orWhere('name', 'Vaardigheden (A)');
                    })->firstOrFail();
            })
            ->pluck('id');

        $question->domains()->sync($domains);
    }

    public function processTags($question, $values)
    {
        $tags = [];
        $tagValues = array_filter(explode("\n", $values));

        foreach ($tagValues as $tagValue) {
            $this->info('Trefwoord: ' . $tagValue);
            $tag = Tag::where('name', $tagValue)->first();

            if (!$tag) {
                $tag = Tag::forceCreate([
                    'course_id' => 1,
                    'name' => $tagValue,
                ]);
            }

            $tags[] = $tag->id;
        }

        $question->tags()->sync($tags);
    }

    public function processQuestionType($question, $value)
    {
        $this->info('Vraagtype verwerken: ' . $value);
        $type = QuestionType::where('name', $value)->first();

        if (!$type) {
            $type = QuestionType::create([
                'course_id' => 1,
                'name' => $value,
            ]);
        }

        $question->update([
            'type_id' => $type->id,
        ]);
    }

    public function processHighlights($question, $value)
    {
        // Delete all highlights
        $question->highlights()->delete();

        // Store highlight
        $question->highlights()->create([
            'text' => $value,
        ]);
    }
}
