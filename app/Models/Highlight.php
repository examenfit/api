<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Highlight extends Model implements Auditable
{
    use HasFactory, HashID, \OwenIt\Auditing\Auditable;

    public $guarded = [];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
