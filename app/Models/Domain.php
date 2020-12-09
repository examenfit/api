<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory, HashID;

    public $with = ['children'];

    public function children()
    {
        return $this->hasMany(Self::class, 'parent_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
