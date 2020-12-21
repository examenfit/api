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
            'type' => $this->type,
            'remark' => $this->remark,
            'sections' => AnswerSectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
