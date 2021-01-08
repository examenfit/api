<?php

namespace App\Http\Resources;

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
        ];
    }
}
