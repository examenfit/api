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
    public $guarded = [];

    public function children()
    {
        return $this->hasMany(Self::class, 'parent_id')
            ->orderBy('name', 'ASC');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function question()
    {
        return $this->belongsToMany(Question::class);
    }

    public function topics()
    {
        return $this->hasManyJson(Topic::class, 'cache->tagsId');
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function setLevelIdAttribute($value)
    {
        $decodedValue = $this->hashToId($value);

        $this->attributes['level_id'] = $decodedValue
            ? $this->attributes['level_id'] = $decodedValue
            : $this->attributes['level_id'] = $value;
    }
}
