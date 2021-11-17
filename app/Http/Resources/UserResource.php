<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user =  auth()->user();
        $switchable = $user && $user->link === $this->link;

        return [
            'id' => $this->hash_id,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'role' => $this->role,
            'newsletter' => $this->newsletter,
            'switchable' => $switchable,
            'data' => json_decode($this->data)
        ];
    }
}
