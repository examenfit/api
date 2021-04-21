<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory, HashID;
    public $guarded = [];

    public function topics()
    {
        return $this->morphedByMany(Topic::class, 'attachable');
    }

    public function questions()
    {
        return $this->morphedByMany(Question::class, 'attachable');
    }

    public function questionAppendix()
    {
        return $this->belongsToMany(Question::class, 'question_appendix');
    }

    public function getUrlAttribute()
    {
        return Storage::url($this->attributes['path']);
    }
}
