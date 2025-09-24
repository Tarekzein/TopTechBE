<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Model;

class AIChatLog extends Model
{
    protected $table = 'ai_chat_logs';

    protected $fillable = [
        'user_id',
        'session_token',
        'user_message',
        'ai_response',
    ];
}
