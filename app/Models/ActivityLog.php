<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    public $fillable = [
      'origin',
      'device_key',
      'session_key',
      'activity',
      'collection_id',
      'question_id',
      'topic_id',
      'email'
    ];
}
