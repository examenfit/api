<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Tag extends Model
{
    use HasFactory, HashID, HasJsonRelationships;

    public $with = ['children'];

    public function children()
    {
        return $this->hasMany(Self::class, 'parent_id')
            ->orderBy('name', 'ASC');
    }

    public function question()
    {
        return $this->belongsToMany(Question::class);
    }

    public function topics()
    {
        return $this->hasManyJson(Topic::class, 'cache->tagsId');
    }
}
