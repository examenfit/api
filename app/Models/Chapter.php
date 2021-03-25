<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Chapter extends Model
{
    use HasFactory, HashID, HasJsonRelationships;

    public $with = ['children', 'methodology'];

    public function methodology()
    {
        return $this->belongsTo(Methodology::class);
    }

    public function children()
    {
        return $this->hasMany(Self::class, 'chapter_id');
    }

    public function parent()
    {
        return $this->belongsTo(Self::class, 'chapter_id');
    }

    public function topics()
    {
        return $this->hasManyJson(Topic::class, 'cache->chapterId');
    }
}
