<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Support\Arr;
use App\Models\Pivot\QuestionTag;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Pivot\DomainQuestion;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model implements Auditable
{
    use HasFactory, HashID, \OwenIt\Auditing\Auditable;

    public $fillable = [
        'topic_id',
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
        return $this->belongsToMany(Domain::class)
            ->using(DomainQuestion::class)
            ->withPivot([
                'id'
            ]);
    }

    public function questionType()
    {
        return $this->belongsTo(QuestionType::class, 'type_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)
            ->using(QuestionTag::class)
            ->withPivot([
                'id'
            ]);
    }

    public function tips()
    {
        return $this->morphMany(Tip::class, 'tippable');
    }

    public function methodologies()
    {
        return $this->belongsToMany(Methodology::class, 'question_methodology')->withPivot('chapter');
    }

    public function addAttachments($attachments)
    {
        $collection = collect($attachments)
            ->pluck('id')
            ->transform(fn ($id) => Hashids::decode($id)[0]);

        return $this->attachments()->sync($collection);
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

    public function setTopicIdAttribute($value)
    {
        $decodedValue = $this->hashToId($value);

        $this->attributes['topic_id'] = $decodedValue
            ? $this->attributes['topic_id'] = $decodedValue
            : $this->attributes['topic_id'] = $value;
    }

    public function setTypeIdAttribute($value)
    {
        $decodedValue = $this->hashToId($value);

        $this->attributes['type_id'] = $decodedValue
            ? $this->attributes['type_id'] = $decodedValue
            : $this->attributes['type_id'] = $value;
    }

    public function addMethodologies($methodologies)
    {
        $this->methodologies()->detach();

        collect($methodologies)->each(function ($item) {
            $this->methodologies()->attach([
                Hashids::decode($item['id'])[0] => ['chapter' => $item['chapter']]
            ]);
        });
    }
}
