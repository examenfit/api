<?php

namespace App\Models;

use App\Support\HashID;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'number',
        'points',
        'introduction',
        'text',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function attachments()
    {
        return $this->morphToMany(Attachment::class, 'attachable');
    }

    // public function addAnswers($answers, $type)
    // {
    //     return $this->
    // }

    public function addAttachments($attachments)
    {
        return $this->attachments()->sync(
            collect($attachments)
                ->pluck('id')
                ->transform(function ($id) {
                    return Hashids::decode($id)[0];
                })
        , false);
    }
}
