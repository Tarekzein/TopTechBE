<?php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class FcmToken extends Model
{
    use Notifiable;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'device_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
