<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user_id', 'status'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
