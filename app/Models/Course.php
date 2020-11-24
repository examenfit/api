<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    public function facets()
    {
        return $this->hasMany(Facet::class)->whereNull('parent_id');
    }
}
