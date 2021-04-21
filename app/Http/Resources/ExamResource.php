<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Auth;
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
            'course_id' => Hashids::encode($this->course_id),
            'status' => $this->status,
            'level' => $this->level,
            'year' => $this->year,
            'term' => $this->term,
            'standardization_value' => $this->standardization_value,
            'is_pilot' => $this->is_pilot,
            'introduction' => $this->introduction,
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'files' => ExamSourceFileResource::collection($this->whenLoaded('files')),
            'course' => new CourseResource($this->whenLoaded('course')),
            'assignment_contents' => $this->when(Auth::user()->isAdmin(), $this->assignment_contents),
        ];
    }
}
