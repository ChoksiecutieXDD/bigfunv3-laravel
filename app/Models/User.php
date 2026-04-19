<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 1. Override the default 'id' primary key
    protected $primaryKey = 'user_id';

    // 2. Disable automatic timestamps (table lacks updated_at)
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password_hash',
        'role',
        'address',
        'age',
        'birthday',
        'gender',
        'contact_no',
        'is_active',
        'reset_token',
        'reset_expires',
        'change_passtime', // <-- ADD THIS
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash', // Hide your custom password column
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'reset_expires' => 'datetime',
            'created_at' => 'datetime',
            'change_passtime' => 'datetime',
            'password_hash' => 'hashed', // Hash the correct column
        ];
    }

    // 2. Tell Laravel's Auth to look at 'password_hash' instead of 'password'
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }
}
