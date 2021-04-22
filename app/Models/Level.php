<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory, HashID;

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
