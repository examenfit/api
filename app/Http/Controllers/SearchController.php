<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Resources\TopicResource;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\CourseResource;
use App\Http\Resources\DomainResource;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\QuestionTypeResource;

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
                $query->whereHas('exam', function (Builder $subQuery) use ($value) {
                    $subQuery->where('level', $value);
                });
            }),
            AllowedFilter::callback('domain', function (Builder $query, $value) {
                $query->whereHas('questions.domains', function (Builder $subQuery) use ($value) {
                    $ids = collect($value)
                        ->map(
                            fn($item) => Hashids::decode($item)[0]
                        )->toArray();

                    $subQuery->whereIn(DB::raw('`domains`.`parent_id`'), $ids)
                        ->orWhereIn(DB::raw('`domains`.`id`'), $ids);
                });
            }),
            AllowedFilter::callback('subdomain', function (Builder $query, $value) {
                $query->whereHas('questions.domains', function (Builder $subQuery) use ($value) {
                    $ids = collect($value)
                        ->map(
                            fn($item) => Hashids::decode($item)[0]
                        )->toArray();
                    $subQuery->whereIn(DB::raw('`domains`.`id`'), $ids);
                });
            }),
            AllowedFilter::callback('year', function (Builder $query, $value) {
                $query->whereHas('exam', function (Builder $subQuery) use ($value) {
                    $subQuery->whereIn('year', $value);
                });
            }),
            AllowedFilter::callback('term', function (Builder $query, $value) {
                $query->whereHas('exam', function (Builder $subQuery) use ($value) {
                    $subQuery->whereIn('term', $value);
                });
            }),
            AllowedFilter::callback('questionType', function (Builder $query, $value) {
                $query->whereHas('questions.questionType', function (Builder $subQuery) use ($value) {
                    $ids = collect($value)
                        ->map(
                            fn($item) => Hashids::decode($item)[0]
                        )->toArray();
                    $subQuery->whereIn('id', $ids);
                });
            }),
            AllowedFilter::callback('complexity', function (Builder $query, $value) {
                $query->whereHas('questions.questionType', function (Builder $subQuery) use ($value) {
                    $subQuery->whereIn('complexity', $value);
                });
            }),
        ])->with('exam', 'questions')->get();

        return TopicResource::collection($topics);
    }
}
