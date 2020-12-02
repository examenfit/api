<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'course_id',
        'status',
        'level',
        'year',
        'term',
        'status',
        'assignment_contents',
    ];

    public $casts = [
        'assignment_contents' => 'array'
    ];

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function questions()
    {
        return $this->hasManyThrough(Question::class, Topic::class);
    }

    public function files()
    {
        return $this->hasMany(ExamSourceFile::class);
    }
}
