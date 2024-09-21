<?php

namespace App\Repositories;

use App\Enums\EtatReferentiel;
use App\Interfaces\ReferentielRepositoryInterface;
use App\Services\FirebaseService;

class ReferentielRepository implements ReferentielRepositoryInterface
{
    protected $firebaseService;
    protected $referentielsRef;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
        $this->referentielsRef = $this->firebaseService->getDatabase()->getReference('referentiels');
    }

    public function all()
    {
        $referentiels = $this->referentielsRef->getValue();
        return $referentiels ?? []; // Return an empty array if no data is found
    }


    public function find($id)
    {
        return $this->referentielsRef->getChild($id)->getValue();
    }

    public function create(array $data)
    {
        // Récupérer le dernier ID et l'incrémenter
        $lastId = $this->firebaseService->getLastReferentielId();
        $newId = $lastId + 1;

        // Créer le nouveau référentiel avec l'ID incrémenté
        $this->firebaseService->createReferentielWithId($data, $newId);

        // Mettre à jour le dernier ID dans Firebase
        $this->firebaseService->updateLastReferentielId($newId);

        return ['id' => $newId] + $data;
    }

    public function update($id, array $data)
    {
        $this->referentielsRef->getChild($id)->update($data);
        return $this->find($id);
    }

    public function delete($id)
    {
        return $this->referentielsRef->getChild($id)->remove();
    }

    public function findById($id)
    {
        return $this->find($id);
    }

    // public function getReferentielById($id)
    // {
    //     return $this->find($id);
    // }

    public function getAllReferentiels()
    {
        return $this->all();
    }

    public function getReferentielsActifs()
    {
        $referentiels = $this->all();
        return array_filter($referentiels, function ($referentiel) {
            return $referentiel['etat'] === EtatReferentiel::ACTIF->value;
        });
    }

    public function deleteReferentiel($id)
    {
        return $this->delete($id);
    }

    public function getArchivedReferentiels()
    {
        $referentiels = $this->all();
        return array_filter($referentiels, function ($referentiel) {
            return $referentiel['etat'] === EtatReferentiel::ARCHIVE->value;
        });
    }

    public function findByCode($code)
    {
        $referentiels = $this->all();
        foreach ($referentiels as $id => $referentiel) {
            if ($referentiel['code'] === $code) {
                return ['id' => $id] + $referentiel;
            }
        }
        return null; // Return null if no match is found
    }
    // public function addCompetenceToReferentiel($referentielId, $competence)
    // {
    //     $competenceRef = $this->referentielsRef->getChild($referentielId)->getChild('competences')->push($competence);
    //     return ['id' => $competenceRef->getKey()] + $competence;
    // }

    public function addCompetenceToReferentiel($referentielId, array $competence)
    {
        $competencesRef = $this->referentielsRef->getChild($referentielId)->getChild('competences');
        $competenceRef = $competencesRef->push($competence);

        error_log('Compétence ajoutée : ' . json_encode($competence) . ' avec ID : ' . $competenceRef->getKey());

        return ['id' => $competenceRef->getKey()] + $competence;
    }

    public function getCompetencesByReferentielId($referentielId)
    {
        $competences = $this->referentielsRef->getChild($referentielId)->getChild('competences')->getValue() ?? [];
        error_log('Compétences récupérées pour le référentiel ' . $referentielId . ': ' . json_encode($competences));
        return $competences;
    }


    public function getReferentielById($id)
    {
        $referentiel = $this->find($id);
        if ($referentiel) {
            $referentiel['competences'] = $this->getCompetencesByReferentielId($id);
        }
        return $referentiel;
    }
}