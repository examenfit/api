<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnotationResource extends JsonResource
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
            'stream_id' => Hashids::encode($this->stream_id),
            'name' => $this->name,
            'type' => $this->type,
            'position' => $this->position,
            'stream' => new StreamResource($this->whenLoaded('stream')),
            'children' => AnnotationResource::collection($this->whenLoaded('children')),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
