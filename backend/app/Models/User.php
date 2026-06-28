<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'brand_email', 'personal_email', 'password', 'role', 'status',
        'phone', 'interests', 'rejection_reason',
        'approved_at', 'rejected_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at'       => 'datetime',
            'rejected_at'       => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'cs']);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }
}
