<?php

namespace App\Models;

use App\Support\HashID;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'number',
        'points',
        'introduction',
        'text',
    ];
    public $with = ['attachments'];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function attachments()
    {
        return $this->morphToMany(Attachment::class, 'attachable');
    }

    public function facets()
    {
        return $this->morphToMany(Facet::class, 'facetable');
    }

    public function addAttachments($attachments)
    {
        $collection = collect($attachments)
            ->pluck('id')
            ->transform(fn ($id) => Hashids::decode($id)[0]);

        return $this->attachments()->sync($collection, false);
    }

    public function addFacets($facets)
    {
        $collection = collect($facets)
            ->pluck('id')
            ->transform(fn ($id) => Hashids::decode($id)[0]);

        return $this->facets()->sync($collection, false);
    }
}
