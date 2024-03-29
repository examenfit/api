<?php

namespace App\Models;

use App\Support\HashID;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class Topic extends Model implements Auditable
{
    use HasFactory, HashID, \OwenIt\Auditing\Auditable;

    protected $auditInclude = [
        'name',
        'introduction',
        'complexity',
        'popularity',
    ];

    public $fillable = [
        'name',
        'introduction',
        'complexity',
        'popularity',
        'cache',
        'position'
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
