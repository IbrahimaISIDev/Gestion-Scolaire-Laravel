<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apprenant extends Model
{
    use SoftDeletes;

    protected $fillable = ['nom', 'prenom', 'email', 'matricule', 'statut', 'promotion_id', 'referentiel_id'];

    // public function promotion()
    // {
    //     return $this->belongsTo(Promotion::class);
    // }

    // public function referentiel()
    // {
    //     return $this->belongsTo(Referentiel::class);
    // }

    // public function presences()
    // {
    //     return $this->hasMany(Presence::class);
    // }

    // public function notes()
    // {
    //     return $this->hasMany(Note::class);
    // }
}