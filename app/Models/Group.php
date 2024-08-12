<?php

namespace App\Models;

use App\Models\Seat;
use App\Models\Collection;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Group extends Model
{
    use HasFactory, HashID, HasRelationships;

    public $fillable = [
        'name',
        'license_id',
        'stream_id',
        'is_active',
        'settings',
        'brin_id',
    ];

    public function license()
    {
        return $this->belongsTo(License::class);
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'group_collection');
    }

    public function seats()
    {
        return $this->belongsToMany(Seat::class, 'seat_group');
    }
}
