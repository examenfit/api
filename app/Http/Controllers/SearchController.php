<?php

namespace App\Http\Controllers;

use Closure;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Sorts\Sort;
use Vinkla\Hashids\Facades\Hashids;
use Spatie\QueryBuilder\AllowedSort;
use App\Http\Resources\TopicResource;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\CourseResource;
use App\Http\Resources\DomainResource;
use App\Http\Resources\MethodologyResource;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\QuestionTypeResource;
use App\Models\Exam;

class SearchController extends Controller
{
    private function topicFilter($query)
    {
        $levels = [1, 'havo'];

        //vwo
        if (request()->level === 'pNQ8O') {
            $levels = [2, 'vwo'];
        }

        return $query->whereIn('cache->level', $levels)
            ->where('cache->examStatus', 'published');
    }

    public function index(Request $request, Course $course)
    {
        $level = Hashids::decode(request()->level)[0] ?? 2;

        $course->load([
            'domains' => function ($query) use ($level) {
                $query
                    ->withCount(['topics' => fn ($query) => $this->topicFilter($query)])
                    ->with(['children' => function ($query) {
                        $query->withCount(['topics' => fn ($query) => $this->topicFilter($query)]);
                    }])
                    ->where('level_id', $level);
            },
            'questionTypes' => function ($query) use ($level) {
                $query->where('level_id', $level)
                    ->withCount([
                        'topics' => fn ($query) => $this->topicFilter($query)
                    ]);
            },
            'topics' => fn ($query) => $this->topicFilter($query),
            'methodologies' => function ($query) use ($level) {
                $query->with(['chapters' => function ($query) use ($level) {
                    $query->where('level_id', $level)
                        ->with(['children' => function ($query) {
                            $query->withCount([
                                'topics' => fn ($query) => $this->topicFilter($query)
                            ])->orderBy('name');
                        }])->withCount([
                            'topics' => fn ($query) => $this->topicFilter($query)
                        ])->orderBy('name');
                }])->withCount(['topics' => fn ($query) => $this->topicFilter($query)]);
            },
        ]);

        $years = $course->topics
            ->countBy(fn ($topic) => $topic->cache['year'])
            ->transform(function ($item, $key) {
                return [
                    'id' => $key,
                    'name' => $key,
                    'topics_count' => $item
                ];
            })->sortByDesc('id')->values();


        $terms = $course->topics
            ->countBy(fn ($topic) => $topic->cache['term'])
            ->transform(function ($item, $key) {
                return [
                    'id' => $key,
                    'name' => $key . 'e tijdvak',
                    'topics_count' => $item
                ];
            })->sortBy('id')->values();

        $complexities = $course->topics
            ->countBy(fn ($topic) => $topic->complexity)
            ->sortDesc()
            ->transform(function ($item, $key) {
                return [
                    'id' => $key,
                    'name' => $key,
                    'topics_count' => $item
                ];
            })
            ->sortBy(function ($value) {
                return array_search($value['id'], ["high", "average", "low"]);
            })
            ->values();

        return [
            'domains' => DomainResource::collection($course->domains),
            'questionTypes' => QuestionTypeResource::collection($course->questionTypes),
            'years' => $years,
            'terms' => $terms,
            'complexities' => $complexities,
            'methodologies' => MethodologyResource::collection($course->methodologies),
            'xyz' => 'XYZ'
/*
            'has_answers' => [
              [ 'id' => null, 'name' => 'exclusief tips/nakijken', 'topics_count' => 1 ],
              [ 'id' => 1, 'name' => 'inclusief tips/nakijken', 'topics_count' => 1 ],
            ]
*/
        ];
    }

    public function results(Request $request, Course $course)
    {
        $topics = QueryBuilder::for(Topic::class)->allowedFilters([
            AllowedFilter::callback('level', function (Builder $query, $value) {
                $level = 'havo';

                //vwo
                if ($value === 'pNQ8O') {
                    $level = 'vwo';
                }

                $query->where('cache->level', $level);
            }),
            AllowedFilter::callback('domain', function (Builder $query, $value) {
                $query->whereHas('questions.domains', function (Builder $subQuery) use ($value) {
                    $ids = collect($value)->map(
                        fn ($item) => Hashids::decode($item)[0]
                    )->toArray();

                    $subQuery->whereIn(DB::raw('`domains`.`parent_id`'), $ids)
                        ->orWhereIn(DB::raw('`domains`.`id`'), $ids);
                });
            }),
            AllowedFilter::callback('subdomain', function (Builder $query, $value) {
                $query->whereHas('questions.domains', function (Builder $subQuery) use ($value) {
                    $ids = collect($value)->map(
                        fn ($item) => Hashids::decode($item)[0]
                    )->toArray();
                    $subQuery->whereIn(DB::raw('`domains`.`id`'), $ids);
                });
            }),
            AllowedFilter::callback('year', function (Builder $query, $value) {
                $query->whereIn('cache->year', $value);
            }),
            AllowedFilter::callback('term', function (Builder $query, $value) {
                $query->whereIn('cache->term', $value);
            }),
            AllowedFilter::callback('questionType', function (Builder $query, $value) {
                $query->where(function ($query) use ($value) {
                    collect($value)->map(
                        fn ($item) => Hashids::decode($item)[0]
                    )->each(
                        fn ($id) => $query->orWhereJsonContains('cache->questionTypesId', [$id])
                    );
                });
            }),
            AllowedFilter::callback('complexity', function (Builder $query, $value) {
                $query->whereIn('complexity', $value);
            }),
            AllowedFilter::callback('tags', function (Builder $query, $value) {
                $ids = collect($value)->map(
                    fn ($item) => Hashids::decode($item)[0]
                )->toArray();
                $query->whereJsonContains('cache->tagsId', $ids);
            }),
            AllowedFilter::callback('methodology', function (Builder $query, $value) {
                $id = Hashids::decode($value)[0];
                $query->whereJsonContains('cache->methodologyId', $id);
            }),
            AllowedFilter::callback('chapter', function (Builder $query, $value) {
                $ids = collect($value)->map(
                    fn ($item) => Hashids::decode($item)[0]
                )->toArray();
                $query->whereJsonContains('cache->chapterId', $ids);
            }),
        ])->allowedSorts([
            'name',
            'popularity',
            AllowedSort::custom('complexity', new class implements Sort
            {
                public function __invoke(Builder $query, bool $descending, string $property)
                {
                    $query->orderByRaw(
                        "FIELD(`complexity`, 'low', 'average', 'high') " .
                            ($descending ? 'DESC' : 'ASC')
                    )->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"averageProportionValue\"')) as unsigned)" .
                            ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('proportion_value', new class implements Sort
            {
                public function __invoke(Builder $query, bool $descending, string $property)
                {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"averageProportionValue\"')) as unsigned)" .
                            ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('year', new class implements Sort
            {
                public function __invoke(Builder $query, bool $descending, string $property)
                {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"year\"')) as unsigned)" .
                            ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('question_count', new class implements Sort
            {
                public function __invoke(Builder $query, bool $descending, string $property)
                {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"questionCount\"')) as unsigned)" .
                            ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('time_in_minutes', new class implements Sort
            {
                public function __invoke(Builder $query, bool $descending, string $property)
                {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"totalTimeInMinutes\"')) as unsigned)" .
                            ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('points', new class implements Sort
            {
                public function __invoke(Builder $query, bool $descending, string $property)
                {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"totalPoints\"')) as unsigned)" .
                            ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
        ])
            ->where('cache->course_id', $course->id)
            ->where('cache->examStatus', 'published');

        return TopicResource::collection($topics->get());
    }
}
