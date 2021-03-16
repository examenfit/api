<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnswerSection extends Model implements Auditable
{
    use HasFactory, HashID, \OwenIt\Auditing\Auditable;

    public $fillable = [
        'correction',
        'text',
        'elaboration',
        'explanation',
        'points',
    ];

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }

    public function tips()
    {
        return $this->morphMany(Tip::class, 'tippable');
    }
}
