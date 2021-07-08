<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Methodology extends Model implements Auditable
{
    use HasFactory, HashID, \OwenIt\Auditing\Auditable, HasJsonRelationships;

    public $fillable = [
        'stream_id',
        'name',
    ];

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->whereNull('chapter_id');
    }

    public function topics()
    {
        return $this->hasManyJson(Topic::class, 'cache->methodologyId');
    }
}
