<?php

namespace App\Models;

use App\Interfaces\UserFirebaseInterface;

class UserFirebase extends FirebaseModel implements UserFirebaseInterface
{
   protected $path = 'users';


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
}
