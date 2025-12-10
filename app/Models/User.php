<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
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
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials.
     *
     * @return string
     */
    public function initials(): string
    {
        $name = trim($this->name);
        
        if (empty($name)) {
            return strtoupper(substr($this->email, 0, 2));
        }

        $words = explode(' ', $name);
        
        if (count($words) >= 2) {
            // First letter of first word and first letter of last word
            return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
        }

        // If only one word, return first two letters
        return strtoupper(substr($name, 0, 2));
    }
}

