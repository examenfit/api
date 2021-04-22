<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionDependencyResource extends JsonResource
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
            'introduction' => $this->introduction,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'appendixes' => AttachmentResource::collection($this->whenLoaded('appendixes')),
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
