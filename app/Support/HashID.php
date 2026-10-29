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
        // relations (create(), associate()) hand over the numeric id, and hashids 14 only decodes strings;
        // a number is not a hashid, callers fall back to the value itself
        if (!is_string($hashid)) {
            return false;
        }

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
