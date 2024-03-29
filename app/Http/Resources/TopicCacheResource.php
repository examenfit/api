<?php

namespace App\Http\Resources;

use App\Models\Tag;
use App\Models\Domain;
use App\Models\Highlight;
use App\Models\Methodology;
use App\Models\QuestionType;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicCacheResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'examStatus' => $this->resource['examStatus'],
            'examAnswers' => $this->resource['examAnswers'],
            'stream_id' => Hashids::encode($this->resource['stream_id']),
            'course' => $this->resource['course'],
            'course_id' => Hashids::encode($this->resource['course_id']),
            'level' => $this->resource['level'],
            'level_id' => Hashids::encode($this->resource['level_id']),
            'year' => $this->resource['year'],
            'term' => $this->resource['term'],
            'totalPoints' => $this->resource['totalPoints'],
            'weightedProportionValue' => $this->resource['weightedProportionValue'],
            'questionCount' => $this->resource['questionCount'],
            'questionsId' => collect($this->resource['questionsId'])->map(
                fn ($id) => Hashids::encode($id)
            ),
            'totalTimeInMinutes' => $this->resource['totalTimeInMinutes'],
            'questionTypes' => QuestionTypeResource::collection(
                QuestionType::hydrate($this->resource['questionTypes'])
            ),
            'tags' => TagResource::collection(Tag::hydrate($this->resource['tags'])),
            'domains' => DomainResource::collection(Domain::hydrate($this->resource['domains'])),
            'highlights' => HighlightResource::collection(Highlight::hydrate($this->resource['highlights'])),
        ];
    }
}
