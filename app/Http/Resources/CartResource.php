<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'name' => $this->name,
            'introduction' => $this->introduction,
            'exam' => new ExamResource($this->whenLoaded('exam')),
            'questions' => QuestionResource::collection($this->questions),
            'cache' => new TopicCacheResource($this->cache),
        ];
    }
}
