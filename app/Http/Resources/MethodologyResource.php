<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MethodologyResource extends JsonResource
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
            'chapter' => $this->whenPivotLoaded('question_methodology', function () {
                return $this->pivot->chapter;
            }, $this->getAttribute('chapter')),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'topics_count' => $this->topics_count,
        ];
    }
}
