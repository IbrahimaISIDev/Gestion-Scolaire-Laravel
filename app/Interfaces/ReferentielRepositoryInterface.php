<?php

namespace App\Interfaces;

interface ReferentielRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data); // Change to match the method name in the repository class
    public function update($id, array $data); // Same here
    public function delete($id);
    public function findById($id);
    public function getReferentielById($id);
    public function getAllReferentiels();
    public function getReferentielsActifs();
    public function deleteReferentiel($id);
    public function getArchivedReferentiels();
    public function findByCode($code);
    public function addCompetenceToReferentiel($referentielId, array $competence);
    public function getCompetencesByReferentielId($referentielId);

}
