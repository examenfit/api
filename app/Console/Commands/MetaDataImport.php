<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Level;
use App\Models\Topic;
use App\Models\Course;
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
        $filePath = $this->ask("What is the path to the file?");

        $courses = Course::all()->mapWithKeys(
            fn ($row) => [$row->id => $row->name]
        )->toArray();

        $this->course_id = array_search(
            $this->choice(
                "What is the ID of the course?",
                $courses
            ),
            $courses
        );

        $levels = Level::query()
            ->where('course_id', $this->course_id)
            ->get()
            ->mapWithKeys(
                fn ($row) => [$row->id => $row->name]
            )->toArray();

        $this->level_id = array_search(
            $this->choice(
                "What is the ID of the level?",
                $levels
            ),
            $levels
        );

        $chapters = Chapter::query()
            ->where('level_id', $this->level_id)
            ->where('methodology_id', 1)
            ->whereNull('chapter_id')
            ->get()
            ->mapWithKeys(
                fn ($row) => [$row->id => $row->name . ' (' . $row->title . ')']
            )->toArray();

        $this->gr_exam_chapter_id = array_search(
            $this->choice(
                "Whats the Exam Chapter ID of Getal & Ruimte?",
                $chapters
            ),
            $chapters
        );

        $chapters = Chapter::query()
            ->where('level_id', $this->level_id)
            ->where('methodology_id', 2)
            ->whereNull('chapter_id')
            ->get()
            ->mapWithKeys(
                fn ($row) => [$row->id => $row->name . ' (' . $row->title . ')']
            )->toArray();

        $this->mw_exam_chapter_id = array_search(
            $this->choice(
                "Whats the Exam Chapter ID of Moderne Wiskunde?",
                $chapters
            ),
            $chapters
        );

        $collection = Excel::toCollection(new ImportsMetaDataImport, $filePath);

        $collection->each(function ($row) {
            $row->each(function ($item) {
                $this->processRow($item);
            });
        });
    }

    public function processRow($row)
    {
        if (!is_null($row['opgave'])) {
            $this->info('Opgave ophalen: ' . $row['opgave']);
            $this->topic = Topic::where('name', $row['opgave'])
                ->whereHas('exam', function ($query) {
                    $level = Level::find($this->level_id);
                    $query->where('level', strtolower($level->name));
                })->firstOrFail();
        }

        if (!is_null($row['vraag_nr'])) {
            $this->info('Vraag ophalen: ' . $row['vraag_nr']);

            $question = Question::query()
                ->where('topic_id', $this->topic->id)
                ->where('number', $row['vraag_nr'])
                ->firstOrFail();

            // $this->processChapters($question, $row);
            // $this->processDomains($question, $row['domeinen']);
            // $this->processTags($question, $row['trefwoorden']);
            $this->processQuestionType($question, $row['vraagtypen']);
            // $this->processHighlights($question, $row['highlights']);
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
                ->where('chapter_id', $this->gr_exam_chapter_id) // Examentraining
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
                ->where('chapter_id', $this->mw_exam_chapter_id) // Examentraining
                ->where('name', trim($item))
                ->firstOrFail()
                ->id;
        }

        $question->chapters()->sync($chapters);
    }

    public function processDomains($question, $values)
    {
        $availableDomains = Domain::query()
            ->with('children')
            ->where('level_id', $this->level_id)
            ->get()
            ->map(function ($item) {
                $items = $item->children->pluck('id');
                return $items->push($item->id);
            })->flatten();

        $domains = collect(explode("\n", $values))
            ->map(function ($domain) {
                $this->info('Domein splitten: ' . $domain);
                return explode(': ', $domain)[0];
            })
            ->map(function ($domain) use ($availableDomains) {
                // if (
                //     $domain === "Exponentiële verbanden"
                //     || $domain === "Exponentiële en logaritmische functie"
                // ) {
                //     $domain = "Exponentiële en logaritmische functies";
                // }

                $this->info('Domein ophalen: ' . $domain);

                return Domain::where('name', 'LIKE', '%(' . $domain . ')')
                    ->whereIn('id', $availableDomains)
                    // ->where(function ($query) {
                    //     $query->whereNotNull('parent_id')
                    //         ->orWhere('name', 'Vaardigheden (A)');
                    // })
                    ->firstOrFail();
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
                    'course_id' => $this->course_id,
                    'level_id' => $this->level_id,
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
        $type = QuestionType::query()
            ->where('level_id', $this->level_id)
            ->where('name', $value)
            ->first();

        if (!$type) {
            $type = QuestionType::create([
                'course_id' => 1,
                'level_id' => $this->level_id,
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
