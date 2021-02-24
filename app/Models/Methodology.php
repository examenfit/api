<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Methodology extends Model
{
    use HasFactory, HashID;

    public function chapters()
    {
        return $this->belongsToMany(Question::class, 'question_methodology')
            ->withPivot(['chapter']);
    }
}
