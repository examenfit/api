<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomingExamResource extends JsonResource
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
            'level' => $this->level,
            'year' => $this->year,
            'term' => $this->term,
            'assignment_file_url' => Storage::disk('public')->url($this->assignment_file_path),
            'appendix_file_url' => Storage::disk('public')->url($this->appendix_file_path),
            'correction_requirement_file_url' => Storage::disk('public')->url($this->correction_requirement_file_path),
            'standardization_url' => $this->standardization_url,
            'assignment_contents' => $this->assignment_contents,
            'exam' => new ExamResource($this->whenLoaded('exam')),
        ];
    }
}
