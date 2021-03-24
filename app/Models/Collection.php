<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'user_id',
        'name',
    ];

    public static function booted()
    {
        static::creating(function ($collection) {
            if (is_null($collection->user_id)) {
                $collection->user_id = auth()->user()->id;
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }

    public function elaborations()
    {
        return $this->hasMany(Elaboration::class);
    }
}
