<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    use HasFactory, HashID;

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function topics()
    {
        return $this->hasManyThrough(Topic::class, Exam::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function questionTypes()
    {
        return $this->hasMany(QuestionType::class)->orderBy('name', 'ASC');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }

    public function methodologies()
    {
        return $this->hasMany(Methodology::class);
    }
}
