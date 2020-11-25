<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Vinkla\Hashids\Facades\Hashids;

trait HashID
{
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->getKey());
    }

    public function hashToId($hashid)
    {
        $decodedValue = Arr::first(Hashids::decode($hashid));

        if (!is_int($decodedValue)) {
            return false;
        }

        return $decodedValue;
    }

    /**
     * @see parent
     */
    public function resolveRouteBinding($value, $field = NULL)
    {
        return $this->findOrFail($this->hashToId($value));
    }

    public function scopeFindByHashId($query, $id)
    {
        return $query->find($this->hashToId($id));
    }

    public function scopeFindOrFailByHashId($query, $id)
    {
        return $query->findOrFail($this->hashToId($id));
    }

    /**
     * @see parent
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }

    /**
     * @see parent
     */
    public function getRouteKeyName()
    {
        return null;
    }
}
