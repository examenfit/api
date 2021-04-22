<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class QuestionType extends Model
{
    use HasFactory, HashID, HasJsonRelationships;

    public $guarded = [];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function topics()
    {
        return $this->hasManyJson(Topic::class, 'cache->questionTypesId');
    }
}
