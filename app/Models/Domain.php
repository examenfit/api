<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Domain extends Model
{
    use HasFactory, HashID, HasJsonRelationships;

    public $with = ['children'];
    public $fillable = [
        'name',
        'stream_id',
        'parent_id'
    ];

    public function parent()
    {
        return $this->belongsTo(Self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Self::class, 'parent_id');
    }

    public function question()
    {
        return $this->belongsToMany(Question::class);
    }

    public function topics()
    {
        return $this->hasManyJson(Topic::class, 'cache->domainId');
    }
}
