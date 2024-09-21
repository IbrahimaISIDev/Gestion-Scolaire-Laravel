<?php

namespace App\Repositories;

use App\Enums\EtatPromotion;
use App\Facades\PromotionFacade;
use App\Models\PromotionFirebase;
use App\Interfaces\PromotionRepositoryInterface;

class PromotionRepository implements PromotionRepositoryInterface
{
    public function getAllPromotions()
    {
        return PromotionFacade::all();
    }

    public function getPromotionById($id)
    {
        return PromotionFacade::find($id);
    }

    public function createPromotion(array $promotionDetails)
    {
        return PromotionFacade::create($promotionDetails);
    }

    // public function updatePromotion($id, array $newDetails)
    // {
    //     return PromotionFacade::whereId($id)->update($newDetails);
    // }

    public function updatePromotion($id, array $newDetails)
    {
        return PromotionFacade::updatePromotion($id, $newDetails);
    }
    public function deletePromotion($id)
    {
        PromotionFacade::destroy($id);
    }

    public function getPromotionEncours()
    {
        return PromotionFacade::getEncours();
    }

    public function addReferentielToPromotion($promotionId, $referentielId)
    {
        $promotion = $this->getPromotionById($promotionId);
        $promotion->referentiels()->attach($referentielId);
    }

    public function removeReferentielFromPromotion($promotionId, $referentielId)
    {
        $promotion = $this->getPromotionById($promotionId);
        $promotion->referentiels()->detach($referentielId);
    }

    public function getPromotionStats($id)
    {
        $promotion = $this->getPromotionById($id);
        return [
            'info' => $promotion,
            'nombre_apprenants' => $promotion->apprenants()->count(),
            'nombre_apprenants_actifs' => $promotion->apprenants()->where('statut', 'actif')->count(),
            'nombre_apprenants_inactifs' => $promotion->apprenants()->where('statut', 'inactif')->count(),
            'referentiels' => $promotion->referentiels()->withCount('apprenants')->get(),
        ];
    }

    public function cloturerPromotion($id)
    {
        $promotion = $this->getPromotionById($id);
        if ($promotion->date_fin->isPast()) {
            $promotion->update(['etat' => EtatPromotion::CLOTURER]);
            // Ici, on pourrait déclencher un job pour envoyer les relevés de notes
            return true;
        }
        return false;
    }

    public function findByLibelle($libelle)
    {
        $promotions = $this->getAllPromotions();

        // Ensure $promotions is an array before looping
        if (!is_array($promotions)) {
            return null;
        }

        foreach ($promotions as $id => $promotion) {
            // Check if $promotion is valid and contains the 'libelle' key
            if (is_array($promotion) && isset($promotion['libelle']) && $promotion['libelle'] === $libelle) {
                return $promotion;
            }
        }

        return null;
    }
}