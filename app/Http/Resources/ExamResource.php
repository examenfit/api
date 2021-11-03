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
        $user = Auth::user();
        $isAdmin = $user && $user->isAdmin();

        return [
            'id' => $this->hash_id,
            'stream_id' => Hashids::encode($this->stream_id),
            'status' => $this->status,
            'notes' => $this->notes,
            'year' => $this->year,
            'term' => $this->term,
            'standardization_value' => $this->standardization_value,
            'is_pilot' => $this->is_pilot,
            'introduction' => $this->introduction,
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'files' => ExamSourceFileResource::collection($this->whenLoaded('files')),
            'stream' => new StreamResource($this->whenLoaded('stream')),
            //'level' => new LevelResource($this->whenLoaded('level')),
            'assignment_contents' => $this->when($isAdmin, $this->assignment_contents),
        ];
    }
}
