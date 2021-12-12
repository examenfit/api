<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
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
            'question_id' => Hashids::encode($this->question_id),
            'name' => $this->name,
            'type' => $this->type,
            'position' => $this->position,
            'remark' => $this->remark,
            'status' => $this->status,
            'sections' => AnswerSectionResource::collection($this->whenLoaded('sections')),
            'question' => new QuestionResource($this->whenLoaded('question')),
        ];
    }
}
