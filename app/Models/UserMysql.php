<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // Ajoutez cette ligne

class UserMysql extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles; // Ajoutez HasRoles ici

    protected $fillable = [
        'nom', 
        'prenom', 
        'adresse', 
        'email', 
        'password', 
        'telephone', 
        'photo', 
        'fonction', 
        'statut'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $table = 'users';
}
