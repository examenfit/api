<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnswerSectionResource extends JsonResource
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
            'correction' => $this->correction,
            'text' => $this->text,
            'elaboration' => $this->elaboration,
            'explanation' => $this->explanation,
            'points' => $this->points,
            'tips' => TipResource::collection($this->whenLoaded('tips')),
        ];
    }
}
