<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StreamResource extends JsonResource
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
            'level' => new LevelResource($this->whenLoaded('level')),
            'course' => new CourseResource($this->whenLoaded('course')),
            'exams' => ExamResource::collection($this->whenLoaded('exams')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'questionTypes' => QuestionTypeResource::collection($this->whenLoaded('questionTypes')),
            'methodologies' => MethodologyResource::collection($this->whenLoaded('methodologies')),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
        ];
    }
}