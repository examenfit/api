<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Methodology extends Model implements Auditable
{
    use HasFactory, HashID, \OwenIt\Auditing\Auditable;

    public function chapters()
    {
        return $this->belongsToMany(Question::class, 'question_methodology')
            ->withPivot(['chapter']);
    }
}
