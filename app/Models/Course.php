<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory, HashID;

    public function levels()
    {
        return $this->hasMany(Level::class);
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

    public function methodologies()
    {
        return $this->hasMany(Methodology::class);
    }
}
