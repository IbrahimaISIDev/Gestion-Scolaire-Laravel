<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\EtatReferentiel;
use App\Interfaces\ReferentielFirebaseInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReferentielFirebase extends Model implements ReferentielFirebaseInterface
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'photo_couverture',
        'etat',
    ];

    protected $casts = [
        'etat' => EtatReferentiel::class,
    ];

    public function competences()
    {
        // Relation avec les compétences (à implémenter)
    }

    public function modules()
    {
        // Relation avec les modules (à implémenter)
    }

    public function findByCode($code)
    {
        $referentiels = $this->all(); // Obtenez tous les référentiels de Firebase
        return collect($referentiels)->firstWhere('code', $code); // Utilisez la collection pour filtrer
    }


    public function updateReferentiel($id, array $newDetails)
    {
        $referentiel = $this->find($id);
        if (!$referentiel) {
            return null;
        }

        $updatedReferentiel = array_merge($referentiel, $newDetails);
        $this->reference->getChild($id)->set($updatedReferentiel);

        return $updatedReferentiel;
    }

    // Autres méthodes spécifiques aux référentiels...
}
