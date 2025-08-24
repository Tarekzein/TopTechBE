<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OTP extends Model
{
    use HasFactory;

    protected $table = 'otps'; // Explicitly set table name

    protected $fillable = [
        'email',
        'otp',
        'expires_at',
        'verified_at',
        'token'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isValid()
    {
        return !$this->isExpired() && !$this->verified_at;
    }
}