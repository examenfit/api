<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            'topic_id' => Hashids::encode($this->topic_id),
            'type_id' => Hashids::encode($this->type_id),
            'number' => $this->number,
            'points' => $this->points,
            'time_in_minutes' => $this->time_in_minutes,
            'complexity' => $this->complexity,
            'proportion_value' => $this->proportion_value,
            'introduction' => $this->introduction,
            'text' => $this->text,
            'question_type' => new QuestionTypeResource($this->whenLoaded('questionType')),
            'topic' => new TopicResource($this->whenLoaded('topic')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'appendixes' => AttachmentResource::collection($this->whenLoaded('appendixes')),
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'tips' => TipResource::collection($this->whenLoaded('tips')),
            'methodologies' => MethodologyResource::collection($this->whenLoaded('methodologies')),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'highlights' => HighlightResource::collection($this->whenLoaded('highlights')),
            'dependencies' => QuestionDependencyResource::collection($this->whenLoaded('dependencies')),
            'question_dependency' => $this->whenPivotLoaded('question_dependency', function () {
                return [
                    'introduction' => !!$this->pivot->introduction,
                    'attachments' => !!$this->pivot->attachments,
                    'appendixes' => !!$this->pivot->appendixes,
                ];
            }),
        ];
    }
}
