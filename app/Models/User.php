<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password', 'role'];

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function livreur()
    {
        return $this->hasOne(Livreur::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
