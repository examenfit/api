<?php

namespace App\Imports;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\Domain;
use App\Models\Chapter;
use App\Models\Question;
use App\Models\QuestionType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class MetaDataImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{
    public $console;
    public $topic;

    public function __construct($console)
    {
        $this->console = $console;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->processRow($row);
        }
    }

    public function processRow($row)
    {
        if (!is_null($row['opgave'])) {
            $this->console->info('Opgave ophalen: ' . $row['opgave']);
            $this->topic = Topic::where('name', $row['opgave'])->firstOrFail();
        }

        if (!is_null($row['vraag_nr'])) {
            // dd($row);
            $this->console->info('Vraag ophalen: ' . $row['vraag_nr']);

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
        $this->console->info('Hoofdstuk 1 ophalen: ' . $row['hoofdstuktitel_gr']);
        $process = explode("\n", $row['hoofdstuktitel_gr']);
        foreach ($process as $item) {
            $chapters[] = Chapter::where('methodology_id', 1)
                ->where('title', $item)
                ->firstOrFail()
                ->id;
        }

        $this->console->info('Hoofdstuk 2 ophalen: ' . $row['examentraining_gr']);
        $process = explode("\n", $row['examentraining_gr']);
        foreach ($process as $item) {
            $chapters[] = Chapter::where('methodology_id', 1)
                ->where('chapter_id', 10) // Examentraining
                ->where('name', $item)
                ->firstOrFail()
                ->id;
        }

        // Moderne wiskunde
        $this->console->info('Hoofdstuk 3 ophalen: "' . trim($row['hoofdstuktitel_mw']) . '"');
        $process = explode("\n", $row['hoofdstuktitel_mw']);
        foreach ($process as $item) {
            $chapters[] = Chapter::where('methodology_id', 2)
                ->where('title', trim($item))
                ->firstOrFail()
                ->id;
        }

        $this->console->info('Hoofdstuk 4 ophalen: ' . $row['examentraining_mw']);
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
                $this->console->info('Domein splitten: ' . $domain);
                return explode(': ', $domain)[1];
            })
            ->map(function ($domain) {
                if (
                    $domain === "Exponentiële verbanden"
                    || $domain === "Exponentiële en logaritmische functie"
                ) {
                    $domain = "Exponentiële en logaritmische functies";
                }

                $this->console->info('Domein ophalen: ' . $domain);

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
            $tag = Tag::where('name', $tagValue)->first();

            if (!$tag) {
                $tag = Tag::forceCreate([
                    'course_id' => 1,
                    'name' => $tagValue,
                    'is_havo' => false,
                    'is_vwo' => true,
                ]);
            }

            $tags[] = $tag->id;
        }

        $question->tags()->sync($tags);
    }

    public function processQuestionType($question, $value)
    {
        $this->console->info('Vraagtype verwerken: ' . $value);
        $type = QuestionType::where('name', $value)->first();

        if (!$type) {
            $type = QuestionType::forceCreate([
                'course_id' => 1,
                'name' => $value,
            ]);
        }

        $question->update([
            'question_type' => $type->id,
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
