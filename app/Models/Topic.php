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
    ];
    public $with = ['attachments'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attachments()
    {
        return $this->morphToMany(Attachment::class, 'attachable');
    }

    public function addAttachments($attachments)
    {
        return $this->attachments()->sync(
            collect($attachments)
                ->pluck('id')
                ->transform(function ($id) {
                    return Hashids::decode($id)[0];
                })
        , false);
    }
}
