<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerSection extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'correction',
        'text',
        'elaboration',
        'explanation',
        'points',
    ];

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }

    public function tips()
    {
        return $this->morphMany(Tip::class, 'tippable');
    }
}
