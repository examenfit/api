<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
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
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'author' => $this->when($this->relationLoaded('author'), function () {
                return [
                    'full_name' => $this->author->full_name,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
