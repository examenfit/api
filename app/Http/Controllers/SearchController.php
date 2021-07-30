<?php

namespace App\Http\Controllers;

use Closure;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\Stream;
use App\Models\Methodology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Sorts\Sort;
use Vinkla\Hashids\Facades\Hashids;
use Spatie\QueryBuilder\AllowedSort;
use App\Http\Resources\TagResource;
use App\Http\Resources\TopicResource;
use App\Http\Resources\ChapterResource;
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
    public function search(Request $request, Stream $stream)
    {
        $role = auth()->user()->role;

        if ($role === 'admin' || $role === 'author') {
          $statuses = ['concept', 'published'];
        } else {
          $statuses = ['published'];
        }

        // actually this returns the filter options

        $stream->load([
            'domains' => function ($query) {
                $query
                    ->where('parent_id', null)
                    ->withCount(['topics'])
                    ->with(['children' => function ($query) {
                        $query->withCount(['topics']);
                    }]);
            },
            'questionTypes' => function ($query) {
                $query->withCount(['topics']);
            },
            'topics',
        ]);

        $years = $stream->topics
            ->whereIn('exam.status', $statuses)
            ->countBy(fn ($topic) => $topic->cache['year'])
            ->transform(function ($item, $key) {
                return [
                    'id' => $key,
                    'name' => $key,
                    'topics_count' => $item
                ];
            })->sortByDesc('id')->values();


        $terms = $stream->topics
            ->whereIn('exam.status', $statuses)
            ->countBy(fn ($topic) => $topic->cache['term'])
            ->transform(function ($item, $key) {
                return [
                    'id' => $key,
                    'name' => $key . 'e tijdvak',
                    'topics_count' => $item
                ];
            })->sortBy('id')->values();

        $complexities = $stream->topics
            ->whereNotNull('complexity')
            ->whereIn('exam.status', $statuses)
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

        $has_answers_count = $stream->topics
            ->whereIn('exam.status', $statuses)
            ->where('has_answers', 1)
            ->count();

        $has_no_answers_count = $stream->topics
            ->whereIn('exam.status', $statuses)
            ->where('has_answers', 0)
            ->count();

        $has_answers = [
            [
                'id' => 1,
                'name' => 'Inclusief tips/nakijken voor leerlingen',
                'topics_count' => $has_answers_count
            ],
            [
                'id' => 0,
                'name' => 'Exclusief tips/nakijken voor leerlingen',
                'topics_count' => $has_no_answers_count
            ]
        ];

        $methodologies = Methodology::whereHas(
            'chapters', fn($q) => $q
            ->where('stream_id', $stream->id)
        )
        ->with('chapters',
            fn($q) => $q
                ->where('stream_id', $stream->id)
                ->withCount('topics')
                ->with([
                    'children' => fn($q) => $q
                        ->withCount('topics')
                        ->orderBy('id')
                ])
                ->orderBy('name')
        )
        ->get();

        return [
            'methodologies' => MethodologyResource::collection($methodologies),
            'methodologies' => MethodologyResource::collection($methodologies),
            'domains' => DomainResource::collection($stream->domains),
            'questionTypes' => QuestionTypeResource::collection($stream->questionTypes),
            'years' => $years,
            'terms' => $terms,
            'complexities' => $complexities,
            'has_answers' => $has_answers
        ];
    }

    public function search_results(Request $request, Stream $stream)
    {
        $role = auth()->user()->role;
        if ($role === 'admin') {
            $STATUS = [ 'published','concept' ];
        } else if ($role === 'author') {
            $STATUS = [ 'published','concept' ];
        } else { 
            $STATUS = [ 'published' ];
        }
        $topics = QueryBuilder::for(Topic::class)
        ->allowedFilters([
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
            AllowedFilter::callback('has_answers', function (Builder $query, $value) {
                $query->whereIn('has_answers', $value);
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
                $n = 0;
                foreach ($ids as $id) {
                  if ($n++) {
                    $query->orWhereJsonContains('cache->chapterId', $id);
                  } else {
                    $query->whereJsonContains('cache->chapterId', $id);
                  }
                }
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
            ->where('cache->stream_id', $stream->id)
            ->whereIn('cache->examStatus', $STATUS);

        return TopicResource::collection($topics->get());
    }
}
