<?php

namespace App\Http\Resources;

use App\Models\Tag;
use App\Models\Domain;
use App\Models\QuestionType;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
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
            'id' => $this->hash_id,
            'exam_id' => Hashids::encode($this->exam_id),
            'complexity' => $this->complexity,
            'name' => $this->name,
            'introduction' => $this->introduction,
            'attachments' => AttachmentResource::collection($this->attachments),
            'exam' => new ExamResource($this->whenLoaded('exam')),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'cache' => [
                'level' => $this->cache['level'],
                'year' => $this->cache['year'],
                'term' => $this->cache['term'],
                'totalPoints' => $this->cache['totalPoints'],
                'totalProportionValue' => $this->cache['totalProportionValue'],
                'averageProportionValue' => $this->cache['averageProportionValue'],
                'questionCount' => $this->cache['questionCount'],
                'totalTimeInMinutes' => $this->cache['totalTimeInMinutes'],
                'questionTypes' => QuestionTypeResource::collection(
                    QuestionType::hydrate($this->cache['questionTypes'])
                ),
                'tags' => TagResource::collection(Tag::hydrate($this->cache['tags'])),
                'domains' => DomainResource::collection(Domain::hydrate($this->cache['domains'])),
            ]
        ];
    }
}
