<?php

namespace App\Services;

use App\Enums\EtatPromotion;
use App\Jobs\SendReleveNotesJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    // Fonction pour créer une promotion
    public function createPromotion(array $data)
    {
        DB::beginTransaction();

        try {
            // Vérifier si le libellé de la promotion est unique
            if ($this->promotionRepository->findByLibelle($data['libelle'])) {
                throw new \Exception("Erreur : le libellé de la promotion doit être unique. La promotion '" . $data['libelle'] . "' existe déjà.");
            }

            $data['etat'] = EtatPromotion::INACTIF;

            // Calculer la date de fin ou la durée si nécessaire
            if (!isset($data['date_fin'])) {
                $data['date_fin'] = $this->calculerDateFin($data['date_debut'], $data['duree']);
            } elseif (!isset($data['duree'])) {
                $data['duree'] = $this->calculerDuree($data['date_debut'], $data['date_fin']);
            }

            // Créer la promotion
            $promotion = $this->promotionRepository->createPromotion($data);
            Log::info('Promotion créée : ', ['promotion' => $promotion]);

            // Vérifier si l'objet promotion est valide
            if (!is_object($promotion)) {
                throw new \Exception("La création de la promotion a échoué ou n'a pas retourné un objet Promotion. Données reçues : " . json_encode($promotion));
            }

            // Mise à jour des référentiels si présents
            if (isset($data['referentiels'])) {
                $this->updatePromotionReferentiels($promotion->id, $data['referentiels']);
            }

            DB::commit();
            return [$promotion];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de la promotion : ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }


    // Fonction pour mettre à jour une promotion existante
    public function updatePromotion($id, array $data)
    {
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

            DB::commit();
            return $updatedPromotion;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Fonction pour changer le statut de la promotion
    public function changePromotionStatus($id, EtatPromotion $newStatus)
    {
        return $this->promotionRepository->updatePromotion($id, ['etat' => $newStatus]);
    }

    // Fonction pour obtenir les statistiques d'une promotion
    public function getPromotionStats($id)
    {
        return $this->promotionRepository->getPromotionStats($id);
    }

    // Fonction pour clôturer une promotion
    public function cloturerPromotion($id)
    {
        $promotion = $this->promotionRepository->getPromotionById($id);
        $dateFin = is_array($promotion) ? $promotion['date_fin'] : $promotion->date_fin;

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

    // Fonction pour mettre à jour les référentiels d'une promotion
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

    // Fonction pour vérifier si un référentiel peut être retiré
    private function canRemoveReferentiel($promotionId, $referentielId)
    {
        // Vérifier si l'utilisateur a le rôle de Manager
        if (auth()->user()->hasRole('Manager')) {
            return true;
        }

        // Pour les autres rôles, vérifier si le référentiel est vide
        $referentiel = $this->referentielRepository->findById($referentielId);
        return $referentiel->apprenants()->where('promotion_id', $promotionId)->count() === 0;
    }

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

    // Implémentation des méthodes de l'interface
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
