<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Support\Arr;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'type_id',
        'number',
        'points',
        'time_in_minutes',
        'complexity',
        'proportion_value',
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

    public function domains()
    {
        return $this->belongsToMany(Domain::class);
    }

    public function questionType()
    {
        return $this->belongsTo(QuestionType::class, 'type_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function addAttachments($attachments)
    {
        $collection = collect($attachments)
            ->pluck('id')
            ->transform(fn ($id) => Hashids::decode($id)[0]);

        return $this->attachments()->sync($collection, false);
    }

    public function addTags($tags)
    {
        $collection = collect($tags)
            ->pluck('id')
            ->transform(fn ($id) => Hashids::decode($id)[0]);

        return $this->tags()->sync($collection);
    }

    public function addDomains($domains)
    {
        $collection = collect($domains)
            ->pluck('id')
            ->transform(fn ($id) => Hashids::decode($id)[0]);

        return $this->domains()->sync($collection);
    }

    public function setDomainIdAttribute($value)
    {
        $decodedValue = $this->hashToId($value);

        $this->attributes['domain_id'] = $decodedValue
            ? $this->attributes['domain_id'] = $decodedValue
            : $this->attributes['domain_id'] = $value;
    }

    public function setTypeIdAttribute($value)
    {
        $decodedValue = $this->hashToId($value);

        $this->attributes['type_id'] = $decodedValue
            ? $this->attributes['type_id'] = $decodedValue
            : $this->attributes['type_id'] = $value;
    }
}
