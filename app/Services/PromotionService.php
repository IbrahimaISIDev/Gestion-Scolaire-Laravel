<?php

namespace App\Services;

use App\Enums\EtatPromotion;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendReleveNotesJob;
use App\Interfaces\PromotionServiceInterface;
use App\Interfaces\PromotionRepositoryInterface;
use App\Interfaces\ReferentielRepositoryInterface;

class PromotionService implements PromotionServiceInterface
{
    protected $promotionRepository;
    protected $referentielRepository;

    public function __construct(
        PromotionRepositoryInterface $promotionRepository,
        ReferentielRepositoryInterface $referentielRepository
    ) {
        $this->promotionRepository = $promotionRepository;
        $this->referentielRepository = $referentielRepository;
    }

    // public function createPromotion(array $data)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $data['etat'] = EtatPromotion::INACTIF;
    //         if (!isset($data['date_fin'])) {
    //             $data['date_fin'] = $this->calculerDateFin($data['date_debut'], $data['duree']);
    //         } elseif (!isset($data['duree'])) {
    //             $data['duree'] = $this->calculerDuree($data['date_debut'], $data['date_fin']);
    //         }

    //         $promotion = $this->promotionRepository->createPromotion($data);

    //         if (isset($data['referentiels'])) {
    //             $this->updatePromotionReferentiels($promotion->id, $data['referentiels']);
    //         }
    //         DB::commit();
    //         return $promotion;
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }

    public function createPromotion(array $data)
    {
        // Démarrage de la transaction
        DB::beginTransaction();

        try {
            // Vérifier que le libellé est unique
            if ($this->promotionRepository->findByLibelle($data['libelle'])) {
                throw new \Exception("Erreur : le libellé de la promotion doit être unique. La promotion '" . $data['libelle'] . "' existe déjà.");
            }

            // Définir l'état par défaut de la promotion
            $data['etat'] = EtatPromotion::INACTIF;

            // Calculer la date de fin ou la durée en fonction des entrées
            if (!isset($data['date_fin'])) {
                // Si la date de fin n'est pas fournie, la calculer à partir de la date de début et la durée
                $data['date_fin'] = $this->calculerDateFin($data['date_debut'], $data['duree']);
            } elseif (!isset($data['duree'])) {
                // Si la durée n'est pas fournie, la calculer à partir de la date de début et de fin
                $data['duree'] = $this->calculerDuree($data['date_debut'], $data['date_fin']);
            }

            // Créer la promotion via le repository
            $promotion = $this->promotionRepository->createPromotion($data);

            // Si des référentiels sont fournis, les associer à la promotion
            if (isset($data['referentiels'])) {
                $this->updatePromotionReferentiels($promotion->id, $data['referentiels']);
            }

            // Commit de la transaction après la réussite des opérations
            DB::commit();
            return $promotion;
        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            DB::rollBack();
            throw $e;  // Rejeter l'exception pour gestion ultérieure
        }
    }

    public function updatePromotion($id, array $data)
    {
        // Démarrage de la transaction
        DB::beginTransaction();

        try {
            $promotion = $this->promotionRepository->getPromotionById($id);

            if ($promotion->etat === EtatPromotion::CLOTURER) {
                throw new \Exception("Une promotion clôturée ne peut pas être modifiée.");
            }

            // Vérifier l'unicité du libellé si on le modifie
            if (isset($data['libelle']) && $data['libelle'] !== $promotion->libelle) {
                if ($this->promotionRepository->findByLibelle($data['libelle'])) {
                    throw new \Exception("Le libellé de la promotion doit être unique.");
                }
            }

            // Gestion de la durée et des dates
            if (isset($data['date_debut']) || isset($data['date_fin']) || isset($data['duree'])) {
                $date_debut = $data['date_debut'] ?? $promotion->date_debut;
                $date_fin = $data['date_fin'] ?? $promotion->date_fin;
                $duree = $data['duree'] ?? $promotion->duree;

                if (isset($data['date_debut']) && isset($data['date_fin'])) {
                    $data['duree'] = $this->calculerDuree($date_debut, $date_fin);
                } elseif (isset($data['date_debut']) && isset($data['duree'])) {
                    $data['date_fin'] = $this->calculerDateFin($date_debut, $duree);
                } elseif (isset($data['date_fin']) && isset($data['duree'])) {
                    $data['date_debut'] = $this->calculerDateDebut($date_fin, $duree);
                }
            }

            // Gestion de l'état
            if (isset($data['etat'])) {
                $newEtat = EtatPromotion::from($data['etat']);
                if ($newEtat === EtatPromotion::ACTIF) {
                    $promotionEnCours = $this->promotionRepository->getPromotionEncours();
                    if ($promotionEnCours && $promotionEnCours->id !== $id) {
                        throw new \Exception("Une autre promotion est déjà en cours. Veuillez d'abord la désactiver.");
                    }
                }
                $data['etat'] = $newEtat;
            }

            // Mise à jour de la promotion
            $updatedPromotion = $this->promotionRepository->updatePromotion($id, $data);

            // Mise à jour des référentiels si fournis
            if (isset($data['referentiels'])) {
                $this->updatePromotionReferentiels($id, $data['referentiels']);
            }

            // Commit de la transaction
            DB::commit();

            return $updatedPromotion;
        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            DB::rollBack();
            throw $e;
        }
    }


    // public function changePromotionStatus($id, EtatPromotion $newStatus)
    // {
    //     if ($newStatus === EtatPromotion::ACTIF) {
    //         $promotionEnCours = $this->promotionRepository->getPromotionEncours();
    //         if ($promotionEnCours && $promotionEnCours->id !== $id) {
    //             throw new \Exception("Une autre promotion est déjà en cours.");
    //         }
    //     }

    //     return $this->promotionRepository->updatePromotion($id, ['etat' => $newStatus]);
    // }


    public function changePromotionStatus($id, EtatPromotion $newStatus)
    {
        return $this->promotionRepository->updatePromotion($id, ['etat' => $newStatus]);
    }

    public function getPromotionStats($id)
    {
        return $this->promotionRepository->getPromotionStats($id);
    }

    public function cloturerPromotion($id)
    {
        $promotion = $this->promotionRepository->getPromotionById($id);

        // Vérifier si $promotion est un tableau ou un objet et accéder à la date de fin en conséquence
        $dateFin = is_array($promotion) ? $promotion['date_fin'] : $promotion->date_fin;

        // Convertir la date en instance de Carbon pour effectuer la vérification
        if (\Carbon\Carbon::parse($dateFin)->isPast()) {
            $result = $this->promotionRepository->updatePromotion($id, ['etat' => EtatPromotion::CLOTURER]);

            if ($result) {
                // Lancer le job pour envoyer le relevé de notes
                SendReleveNotesJob::dispatch($id);
            }
            return $result;
        }

        return false;
    }

    public function updatePromotionReferentiels($promotionId, array $referentielIds)
    {
        $promotion = $this->promotionRepository->getPromotionById($promotionId);
        if ($promotion->etat === EtatPromotion::CLOTURER) {
            throw new \Exception("Une promotion clôturée ne peut pas être modifiée.");
        }

        $currentReferentiels = $promotion->referentiels()->pluck('id')->toArray();
        $toAdd = array_diff($referentielIds, $currentReferentiels);
        $toRemove = array_diff($currentReferentiels, $referentielIds);

        foreach ($toAdd as $referentielId) {
            $referentiel = $this->referentielRepository->findById($referentielId);
            if ($referentiel->etat !== 'actif') {
                throw new \Exception("Seuls les référentiels actifs peuvent être ajoutés à une promotion.");
            }
            $this->promotionRepository->addReferentielToPromotion($promotionId, $referentielId);
        }

        foreach ($toRemove as $referentielId) {
            $canRemove = $this->canRemoveReferentiel($promotionId, $referentielId);
            if ($canRemove) {
                $this->promotionRepository->removeReferentielFromPromotion($promotionId, $referentielId);
            } else {
                throw new \Exception("Le référentiel ne peut pas être retiré car il contient des apprenants.");
            }
        }

        return $this->promotionRepository->getPromotionById($promotionId);
    }

    private function canRemoveReferentiel($promotionId, $referentielId)
    {
        // Check if the user has the Manager role
        if (auth()->user()->hasRole('Manager')) {
            return true;
        }

        // For other roles (like CM), check if the referentiel is empty
        $referentiel = $this->referentielRepository->findById($referentielId);
        return $referentiel->apprenants()->where('promotion_id', $promotionId)->count() === 0;
    }
    // private function calculerDateFin($dateDebut, $duree)
    // {
    //     return \Carbon\Carbon::parse($dateDebut)->addMonths($duree);
    // }

    // private function calculerDuree($dateDebut, $dateFin)
    // {
    //     return \Carbon\Carbon::parse($dateDebut)->diffInMonths(\Carbon\Carbon::parse($dateFin));
    // }

    // Fonction utilitaire pour calculer la durée entre deux dates
    private function calculerDuree($dateDebut, $dateFin)
    {
        $debut = new \DateTime($dateDebut);
        $fin = new \DateTime($dateFin);
        $interval = $debut->diff($fin);
        return $interval->days;
    }

    // Fonction utilitaire pour calculer la date de début
    private function calculerDateDebut($dateFin, $duree)
    {
        $fin = new \DateTime($dateFin);
        return $fin->sub(new \DateInterval("P{$duree}D"))->format('Y-m-d');
    }

    // Fonction utilitaire pour calculer la date de fin
    private function calculerDateFin($dateDebut, $duree)
    {
        $debut = new \DateTime($dateDebut);
        return $debut->add(new \DateInterval("P{$duree}D"))->format('Y-m-d');
    }
    public function getAllPromotions()
    {
        return $this->promotionRepository->getAllPromotions();
    }

    public function getPromotionById($id)
    {
        return $this->promotionRepository->getPromotionById($id);
    }

    public function getPromotionEncours()
    {
        return $this->promotionRepository->getPromotionEncours();
    }
}
