<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    public $fillable = [
        'first_name',
        'last_name',
        'email',
        'newsletter',
        'license',
        'activation_code',
        'activated',
        'stream_slugs'
    ];

    public function getActivationUrl()
    {
        $app = config('app.dashboard_url');
        $code = $this->activation_code;

        return "http://localhost:3000/activate/$code";
        return "$app/activate/$code";
    }
}
