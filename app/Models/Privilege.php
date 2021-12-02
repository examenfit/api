<?php

namespace App\Models;

use App\Support\HashID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Privilege extends Model
{
    use HasFactory, HashID;

    public $fillable = [
        'actor_seat_id',
        'action',
        'object_type',
        'object_id',
        'begin',
        'end'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'actor_seat_id');
    }

    public static function granted($action, $resource)
    {
      $user = auth()->user();
      if (!$user) {
        return 0;
      }
      if ($user->role === 'admin') {
        return 1;
      }
      return DB::selectOne("
        select count(*) as granted
        from privileges, seats, licenses
        where seats.id = privileges.actor_seat_id
          and seats.license_id = licenses.id
          and seats.user_id = ?
          and seats.is_active
          and privileges.action = ?
          and privileges.object_id = ?
          and privileges.begin < now()
          and privileges.end > now()
          and privileges.is_active
          and licenses.begin < now()
          and licenses.end > now()
          and licenses.is_active
      ", [
        $user->id,
        $action,
        $resource->id
      ])->granted;
    }
}
