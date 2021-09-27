<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Privilege extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'actor_seat_id',
        'action',
        'object_type',
        'object_id',
        'begin',
        'end'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
