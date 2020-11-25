<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory, HashID;

    public function facets()
    {
        return $this->hasMany(Facet::class)->whereNull('parent_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
