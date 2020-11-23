<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerSection extends Model
{
    use HasFactory;
    public $fillable = [
        'text',
        'points',
    ];

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}
