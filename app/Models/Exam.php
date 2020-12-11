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
        'standardization_value',
        'status',
        'assignment_contents',
    ];

    public $casts = [
        'assignment_contents' => 'array'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

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

    public function questionAttachments()
    {
        return $this->hasManyThrough(Attachment::class, Question::class);
    }

    public function setCourseIdAttribute($value)
    {
        $decodedValue = $this->hashToId($value);

        $this->attributes['course_id'] = $decodedValue
            ? $this->attributes['course_id'] = $decodedValue
            : $this->attributes['course_id'] = $value;
    }
}
