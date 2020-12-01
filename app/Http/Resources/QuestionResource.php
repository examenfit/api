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
            'number' => $this->number,
            'points' => $this->points,
            'proportion_value' => $this->proportion_value,
            'introduction' => $this->introduction,
            'text' => $this->text,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'answers' => AnswerResource::collection($this->whenLoaded('answers'))
        ];
    }
}
