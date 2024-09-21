<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferentielRequest extends FormRequest
{
    public function rules()
    {
        return [
            'code' => 'required|string|max:255',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'photo_couverture' => 'nullable|url',
            'etat' => 'required|in:active,inactive', // selon vos valeurs possibles
        ];
    }

    public function authorize()
    {
        return true; // Changez cela selon votre logique d'autorisation
    }
}

