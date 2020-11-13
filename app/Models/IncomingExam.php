<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingExam extends Model
{
    use HasFactory, HashID;

    public $guarded = [];
    public $casts = [
        'assignment_contents' => 'array',
    ];
}
