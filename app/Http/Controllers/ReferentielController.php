<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreReferentielRequest;
use App\Http\Requests\UpdateReferentielRequest;
use App\Interfaces\ReferentielServiceInterface;

class ReferentielController extends Controller
{
    protected $referentielService;

    public function __construct(ReferentielServiceInterface $referentielService)
    {
        $this->referentielService = $referentielService;
    }

    public function index(Request $request)
    {
        $etat = $request->query('etat');

        if ($etat === 'actif') {
            return response()->json($this->referentielService->getReferentielsActifs());
        } elseif ($etat === 'archivé') {
            return response()->json($this->referentielService->getArchivedReferentiels());
        }

        return response()->json($this->referentielService->getAllReferentiels());
    }


    public function store(StoreReferentielRequest $request)
    {
        $referentiel = $this->referentielService->createReferentiel($request->validated());
        return response()->json([
            'message' => __('messages.referentiel_created'),
            'data' => $referentiel,
        ], 201);
    }


    public function show($id, Request $request)
    {
        $referentiel = $this->referentielService->getReferentielById($id);

        if ($request->query('filter') === 'competences') {
            // Récupère les compétences associées
            $competences = $this->referentielService->getCompetencesByReferentielId($id);
            return response()->json([
                'referentiel' => $referentiel,
                'competences' => $competences
            ]);
        }

        return response()->json($referentiel);
    }


    public function update(UpdateReferentielRequest $request, $id)
    {
        $referentiel = $this->referentielService->updateReferentiel($id, $request->validated());
        return response()->json($referentiel);
    }

    public function destroy($id)
    {
        $this->referentielService->deleteReferentiel($id);
        return response()->json(null, 204);
    }

    public function archived()
    {
        return response()->json($this->referentielService->getArchivedReferentiels());
    }
    public function deleteReferentiel($id)
    {
        $this->referentielService->updateReferentiel($id, ['etat' => 'archivé']);
        return response()->json(['message' => 'Referentiel archivé avec succès'], 204);
    }


    // Autres méthodes pour gérer les compétences et les modules...
}
