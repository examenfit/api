<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'name',
        'type',
        'remark',
        'position',
    ];

    public function sections()
    {
        return $this->hasMany(AnswerSection::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
