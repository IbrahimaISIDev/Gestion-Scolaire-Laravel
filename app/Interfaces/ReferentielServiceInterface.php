<?php

namespace App\Interfaces;

interface ReferentielServiceInterface
{
    public function createReferentiel(array $data);
    public function updateReferentiel($id, array $data);
    public function getReferentielById($id);
    public function getAllReferentiels();
    public function getReferentielsActifs();
    public function deleteReferentiel($id);
    public function getArchivedReferentiels();
    public function getCompetencesByReferentielId($referentielId);

}