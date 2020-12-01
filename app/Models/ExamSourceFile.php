<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSourceFile extends Model
{
    use HasFactory, HashID;

    public $guarded = [];
}
