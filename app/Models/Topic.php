<?php

namespace App\Models;

use App\Support\HashID;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Topic extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'name',
        'introduction',
        'complexity',
        'popularity',
        'cache',
    ];

    public $with = ['attachments'];

    public $casts = [
        'cache' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('number', 'ASC');
    }

    public function attachments()
    {
        return $this->morphToMany(Attachment::class, 'attachable');
    }

    public function highlights()
    {
        return $this->hasMany(Highlight::class);
    }

    public function addAttachments($attachments)
    {
        return $this->attachments()->sync(
            collect($attachments)
                ->pluck('id')
                ->transform(function ($id) {
                    return Hashids::decode($id)[0];
                })
        );
    }
}
