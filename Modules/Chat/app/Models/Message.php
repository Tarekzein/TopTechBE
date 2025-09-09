<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'message',
        'read_at'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }
}
