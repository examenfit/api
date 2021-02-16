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
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\QuestionTypeResource;
use App\Models\Exam;

class SearchController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $course->load([
            'domains',
            'questionTypes',
            'exams' => fn($q) => $q->orderBy('year', 'ASC')->orderBy('term', 'ASC'),
        ]);

        $yearTerm = collect();
        $years = collect();
        $terms = collect();
        foreach ($course->exams as $exam) {
            $years->push($exam->year);
            $terms->push($exam->term);
            $yearTerm->push(collect([
                'year' => $exam->year,
                'term' => $exam->term,
            ]));
        }

        return [
            'domains' => DomainResource::collection($course->domains),
            'questionTypes' => QuestionTypeResource::collection($course->questionTypes),
            'years' => $years->unique()->values()->toArray(),
            'terms' => $terms->unique()->values()->toArray(),
            'yearTerm' => $yearTerm,
        ];
    }

    public function results(Request $request, Course $course)
    {
        $topics = QueryBuilder::for(Topic::class)->allowedFilters([
            AllowedFilter::callback('level', function (Builder $query, $value) {
                $query->where('cache->level', $value);
            }),
            AllowedFilter::callback('domain', function (Builder $query, $value) {
                $query->whereHas('questions.domains', function (Builder $subQuery) use ($value) {
                    $ids = collect($value)->map(
                        fn($item) => Hashids::decode($item)[0]
                    )->toArray();

                    $subQuery->whereIn(DB::raw('`domains`.`parent_id`'), $ids)
                        ->orWhereIn(DB::raw('`domains`.`id`'), $ids);
                });
            }),
            AllowedFilter::callback('subdomain', function (Builder $query, $value) {
                $query->whereHas('questions.domains', function (Builder $subQuery) use ($value) {
                    $ids = collect($value)->map(
                        fn($item) => Hashids::decode($item)[0]
                    )->toArray();
                    $subQuery->whereIn(DB::raw('`domains`.`id`'), $ids);
                });
            }),
            AllowedFilter::callback('year', function (Builder $query, $value) {
                $query->where('cache->year', $value);
            }),
            AllowedFilter::callback('term', function (Builder $query, $value) {
                $query->where('cache->term', $value);
            }),
            AllowedFilter::callback('questionType', function (Builder $query, $value) {
                $ids = collect($value)->map(
                    fn($item) => Hashids::decode($item)[0]
                )->toArray();
                $query->whereJsonContains('cache->questionTypesId', $ids);
            }),
            AllowedFilter::callback('complexity', function (Builder $query, $value) {
                $query->where('complexity', $value);
            }),
            AllowedFilter::callback('tags', function (Builder $query, $value) {
                $ids = collect($value)->map(
                    fn($item) => Hashids::decode($item)[0]
                )->toArray();
                $query->whereJsonContains('cache->tagsId', $ids);
            }),
        ])->allowedSorts([
            'name',
            AllowedSort::custom('complexity', new class implements Sort {
                public function __invoke(Builder $query, bool $descending, string $property) {
                    $query->orderByRaw(
                        "FIELD(`complexity`, 'low', 'average', 'high') ".
                        ($descending ? 'DESC' : 'ASC')
                    )->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"averageProportionValue\"')) as unsigned)".
                        ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('proportion_value', new class implements Sort {
                public function __invoke(Builder $query, bool $descending, string $property) {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"averageProportionValue\"')) as unsigned)".
                        ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('year', new class implements Sort {
                public function __invoke(Builder $query, bool $descending, string $property) {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"year\"')) as unsigned)".
                        ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('question_count', new class implements Sort {
                public function __invoke(Builder $query, bool $descending, string $property) {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"questionCount\"')) as unsigned)".
                        ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('time_in_minutes', new class implements Sort {
                public function __invoke(Builder $query, bool $descending, string $property) {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"totalTimeInMinutes\"')) as unsigned)".
                        ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
            AllowedSort::custom('points', new class implements Sort {
                public function __invoke(Builder $query, bool $descending, string $property) {
                    $query->orderByRaw(
                        "cast(json_unquote(json_extract(`cache`, '$.\"totalPoints\"')) as unsigned)".
                        ($descending ? 'DESC' : 'ASC')
                    );
                }
            }),
        ])->with('highlights')->get();


        return TopicResource::collection($topics);
    }
}
