<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Elaboration extends Model
{
    use HasFactory, HashID;

    public $guarded = [];

    public static function booted()
    {
        static::creating(function ($elaboration) {
            if (auth()->user() && is_null($elaboration->user_id)) {
                $elaboration->user_id = auth()->user()->id;
            }
        });
    }
}
