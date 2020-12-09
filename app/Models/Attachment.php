<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory, HashID;
    public $guarded = [];

    public function topics()
    {
        return $this->morphedByMany(Topic::class, 'attachable');
    }

    public function questions()
    {
        return $this->morphedByMany(Question::class, 'attachable');
    }
}
