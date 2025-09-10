<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    public $incrementing = false; // عشان الـ UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'content',
        'sender_id',
        'receiver_id',
        'is_alert',
        'is_admin',
        'read_at',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(\App\Models\User::class, 'receiver_id');
    }
    
}
