<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Collection extends Model
{
    use HasFactory, HashID, HasRelationships;

    public $fillable = [
        'user_id',
        'name',
        'download_type',
        'partial_topics',
        'complete_topics'
    ];

    public static function booted()
    {
        static::creating(function ($collection) {
            if (is_null($collection->user_id)) {
                $collection->user_id = auth()->user()->id;
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }

    public function topics()
    {
        return $this->hasManyDeep(
            Topic::class,
            ['collection_question', Question::class],
            ['collection_id', 'id', 'id'],
            ['id', 'question_id', 'topic_id']
        )->distinct();
    }

    public function elaborations()
    {
        return $this->hasMany(Elaboration::class);
    }
}
