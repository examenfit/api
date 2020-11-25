<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facet extends Model
{
    use HasFactory, HashID;

    public $casts = [
        'is_singular' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function parent()
    {
        return $this->belongsTo(Self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Self::class, 'parent_id');
    }
}
