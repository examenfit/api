<?php

namespace App\Console\Commands;

use App\Models\Topic;
use Illuminate\Console\Command;

class SetTopicCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ef:cache:topics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Topic cache';

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
        Topic::with([
            'questions.domains.parent',
            'questions.tags',
            'questions.questionType',
            'questions.chapters',
            'questions.highlights',
            'exam',
            'exam.stream.course',
            'exam.stream.level',
        ])->get()->each(function ($topic) {
            $proportionSum = 0;
            $cache = collect([
                'stream_id' => $topic->exam->stream->id,
                'course_id' => $topic->exam->stream->course_id,
                'level_id' => $topic->exam->stream->level_id,
                'examStatus' => $topic->exam->status,
                'course' => $topic->exam->stream->course->name,
                'level' => $topic->exam->stream->level->name,
                'year' => $topic->exam->year,
                'term' => $topic->exam->term,
                'totalPoints' => 0,
                'weightedProportionValue' => 0,
                'questionCount' => count($topic->questions),
                'questionsId' => collect(),
                'totalTimeInMinutes' => 0,
                'questionTypes' => collect(),
                'questionTypesId' => [],
                'tags' => collect(),
                'tagsId' => [],
                'domains' => collect(),
                'domainId' => [],
                'methodologyId' => collect(),
                'chapterId' => collect(),
                'highlights' => collect(),
            ]);

            $topic->questions->each(function ($question) use (&$cache, &$proportionSum) {
                $cache['questionsId']->push($question->id);
                $cache['totalPoints'] += $question->points;
                $cache['totalTimeInMinutes'] += $question->time_in_minutes;
                $proportionSum += $question->points * $question->proportion_value;

                if ($question->questionType) {
                    $cache['questionTypes'] = $cache['questionTypes']->push([
                        'id' => $question->questionType->id,
                        'name' => $question->questionType->name,
                    ])->unique('id')->values();
                    $cache['questionTypesId'] = $cache['questionTypes']->pluck('id');
                }

                $question->chapters->each(function ($chapter) use (&$cache) {
                    if ($chapter->parent_id) {
                        $cache['chapterId']->push($chapter->parent_id);
                    }

                    $cache['chapterId']->push($chapter->id);

                    if (!$cache['methodologyId']->contains($chapter->methodology_id)) {
                        $cache['methodologyId']->push($chapter->methodology_id);
                    }
                });

                $question->tags->each(function ($tag) use (&$cache) {
                    $cache['tags'] = $cache['tags']->push([
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ])->unique('id')->values();
                });
                $cache['tagsId'] = $cache['tags']->pluck('id');

                $question->domains->each(function ($domain) use (&$cache) {
                    if ($domain->parent) {
                        $index = null;

                        if (!$cache['domains']->contains('id', $domain->parent->id)) {
                            $cache['domains']->push(collect([
                                'id' => $domain->parent->id,
                                'name' => $domain->parent->name,
                                'children' => collect(),
                            ]));

                            $index = count($cache['domains']) - 1;
                        } else {
                            $index = $cache['domains']->search(
                                fn ($item) => $item['id'] === $domain->parent->id
                            );
                        }

                        if (!$cache['domains'][$index]['children']->contains('id', $domain->id)) {
                            $cache['domains'][$index]['children']->push([
                                'id' => $domain->id,
                                'name' => $domain->name,
                            ]);
                        }
                    } else {
                        if (!$cache['domains']->contains('id', $domain->id)) {
                            $cache['domains']->push(collect([
                                'id' => $domain->id,
                                'name' => $domain->name,
                                'children' => collect(),
                            ]));
                        }
                    }
                });

                $question->highlights->each(function ($highlight) use (&$cache) {
                    // dd($highlight);
                    $cache['highlights']->push([
                        'id' => $highlight->id,
                        'text' => $highlight->text,
                    ]);
                });
            });

            $cache['domainId'] = $cache['domains']
                ->flatten()
                ->filter(fn ($item) => is_int($item))
                ->values();

            if ($cache['questionCount'] && $cache['totalPoints'] > 0) {
                $cache['weightedProportionValue'] =
                    round($proportionSum / $cache['totalPoints']);
            }

            $topic->update(['cache' => $cache->toArray()]);
        });
    }
}
