<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exam extends Model implements Auditable
{
    use HasFactory, HashID, \OwenIt\Auditing\Auditable;

    public $fillable = [
        'stream_id',
        'status',
        'notes',
        'year',
        'term',
        'standardization_value',
        'is_pilot',
        'show_answers',
        'status',
        'introduction',
        'assignment_contents',
    ];

    public $casts = [
        'assignment_contents' => 'array'
    ];

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function topics()
    {
        return $this->hasMany(Topic::class)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC');
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
}
