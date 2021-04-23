<?php

namespace App\Http\Resources;

use App\Models\Methodology;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'exams' => ExamResource::collection($this->whenLoaded('exams')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'questionTypes' => QuestionTypeResource::collection($this->whenLoaded('questionTypes')),
            'methodologies' => MethodologyResource::collection($this->whenLoaded('methodologies')),
            'levels' => LevelResource::collection($this->whenLoaded('levels')),
        ];
    }
}
