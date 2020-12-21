<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory, HashID;

    public $with = ['children'];

    public function children()
    {
        return $this->hasMany(Self::class, 'parent_id')
            ->orderBy('name', 'ASC');
    }

    public function question()
    {
        return $this->belongsToMany(Question::class);
    }
}
