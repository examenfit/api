<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'license_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'token',
        'visible',
        'role'
    ];

    public function license()
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'seat_group');
    }

    public function privileges()
    {
        return $this->hasMany(Privilege::class, 'actor_seat_id');
    }
}
