<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
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
            'status' => $this->status,
            'level' => $this->level,
            'year' => $this->year,
            'term' => $this->term,
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'files' => ExamSourceFileResource::collection($this->whenLoaded('files')),
            'assignment_contents' => $this->assignment_contents,
        ];
    }
}
