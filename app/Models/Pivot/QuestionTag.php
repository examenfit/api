<?php

namespace App\Models\Pivot;

use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionTag extends Pivot implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
    public $incrementing = true;

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName()) ?? 0;
    }

    public function transformAudit(array $data): array
    {
        if (Arr::has($data, 'old_values.tag_id')) {
            $data['old_values']['tag_name'] = optional(Tag::find($this->getAttribute('tag_id')))->name;
        }

        if (Arr::has($data, 'new_values.tag_id')) {
            $data['new_values']['tag_name'] = optional(Tag::find($this->getAttribute('tag_id')))->name;
        }

        return $data;
    }
}
