<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Annotation extends Model
{
    use HasFactory, HashID, HasJsonRelationships;

    //public $with = ['children'];
    public $guarded = [];

    public function children()
    {
        return $this->hasMany(Self::class, 'parent_id')
            ->orderBy('position', 'ASC')
            ->orderBy('name', 'ASC');
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'question_annotation', 'annotation_id');
    }

    //public function topics()
    //{
    //    return $this->hasManyJson(Topic::class, 'cache->tagsId');
    //}
}
