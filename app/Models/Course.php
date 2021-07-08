<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory, HashID;

    public function streams()
    {
        return $this->hasMany(Stream::class);
    }
}
