<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Cviebrock\EloquentSluggable\Sluggable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Modules\Vendor\Models\Vendor;
use Modules\Store\Models\Order;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, Sluggable, HasRoles, HasApiTokens;

    protected $fillable = [
        'first_name',
        'last_name',
        'slug',
        'email',
        'password',
        
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'first_name'
            ]
        ];
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }

    /**
     * Route notifications for the Vonage channel.
     */
    // public function routeNotificationForVonage()
    // {
    //     return $this->phone; // Make sure you have phone field
    // }

    /**
     * Route notifications for the database channel.
     */
    public function routeNotificationForDatabase()
    {
        return $this->id;
    }
}
