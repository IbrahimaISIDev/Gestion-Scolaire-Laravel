<?php

namespace App\Services;

use App\Enums\EtatReferentiel;
use App\Interfaces\ReferentielServiceInterface;
use App\Interfaces\ReferentielRepositoryInterface;

class ReferentielService implements ReferentielServiceInterface
{
    protected $referentielRepository;
    protected $firebaseService;

    public function __construct(ReferentielRepositoryInterface $referentielRepository, FirebaseService $firebaseService)
    {
        $this->referentielRepository = $referentielRepository;
        $this->firebaseService = $firebaseService;
    }

    public function createReferentiel(array $data)
    {
        try {
            if ($this->referentielRepository->findByCode($data['code'])) {
                throw new \Exception("Le code du référentiel doit être unique.");
            }

            // Ajoutez un état actif par défaut
            $data['etat'] = EtatReferentiel::ACTIF->value;

            // Assurez-vous que les compétences sont fournies
            if (isset($data['competences']) && is_array($data['competences'])) {
                // Formatez les compétences
                $competences = array_map(function ($competence) {
                    return [
                        'titre' => trim($competence['titre']),
                        'description' => trim($competence['description']),
                    ];
                }, $data['competences']);
            } else {
                $competences = []; // Initialiser comme tableau vide si aucune compétence
            }

            // Créez le référentiel
            $referentiel = $this->referentielRepository->create($data);

            // Ajoutez les compétences au référentiel
            foreach ($competences as $competence) {
                $this->referentielRepository->addCompetenceToReferentiel($referentiel['id'], $competence);
            }

            // Récupérer le référentiel mis à jour avec les compétences
            $referentiel['competences'] = $this->referentielRepository->getCompetencesByReferentielId($referentiel['id']);

            return $referentiel;
        } catch (\Exception $e) {
            error_log('Erreur lors de la création du référentiel: ' . $e->getMessage());
            throw $e;
        }
    }


    public function updateReferentiel($id, array $data)
    {
        try {
            $referentiel = $this->referentielRepository->getReferentielById($id);

            if (!$referentiel) {
                throw new \Exception("Référentiel non trouvé.");
            }

            if (isset($data['code']) && $data['code'] !== $referentiel['code']) {
                if ($this->referentielRepository->findByCode($data['code'])) {
                    throw new \Exception("Le code du référentiel doit être unique.");
                }
            }

            return $this->referentielRepository->update($id, $data);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getReferentielById($id)
    {
        return $this->referentielRepository->getReferentielById($id);
    }

    public function getAllReferentiels()
    {
        return $this->referentielRepository->getAllReferentiels();
    }

    // public function getReferentielsActifs()
    // {
    //     return $this->referentielRepository->getReferentielsActifs();
    // }

    public function deleteReferentiel($id)
    {
        $referentiel = $this->referentielRepository->getReferentielById($id);
        if (!$referentiel) {
            throw new \Exception("Référentiel non trouvé.");
        }

        // Ici, vous pouvez ajouter une logique pour vérifier si le référentiel est utilisé dans une promotion en cours

        // Au lieu de supprimer, nous mettons à jour l'état à 'ARCHIVE'
        return $this->referentielRepository->update($id, ['etat' => EtatReferentiel::ARCHIVE->value]);
    }

    // public function getArchivedReferentiels()
    // {
    //     return $this->referentielRepository->getArchivedReferentiels();
    // }

    public function getCompetencesByReferentielId($referentielId)
    {
        $competencesRef = $this->firebaseService->getDatabase()->getReference("referentiels/{$referentielId}/competences");
        return $competencesRef->getValue();
    }
    // public function updateReferentiel($id, array $newDetails)
    // {
    //     $this->firebaseService->getDatabase()->getReference("referentiels/{$id}")->update($newDetails);
    //     return $newDetails;
    // }

    public function getReferentielsActifs()
    {
        $referentiels = $this->getAllReferentiels();
        return array_filter($referentiels, function ($referentiel) {
            return $referentiel['etat'] === 'actif';
        });
    }

    public function getArchivedReferentiels()
    {
        $referentiels = $this->getAllReferentiels();
        return array_filter($referentiels, function ($referentiel) {
            return $referentiel['etat'] === 'archivé';
        });
    }
}
