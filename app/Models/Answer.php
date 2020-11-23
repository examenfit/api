<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;
    public $fillable = [
        'type'
    ];

    public function sections()
    {
        return $this->hasMany(AnswerSection::class);
    }
}
