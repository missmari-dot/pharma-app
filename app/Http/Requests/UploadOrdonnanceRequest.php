<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadOrdonnanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fichier_ordonnance' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'client_id' => 'required|exists:clients,id'
        ];
    }

    public function messages(): array
    {
        return [
            'fichier_ordonnance.required' => 'Le fichier d\'ordonnance est requis',
            'fichier_ordonnance.mimes' => 'Format non supporté (PDF, JPG, PNG uniquement)',
            'fichier_ordonnance.max' => 'Taille maximale: 5MB'
        ];
    }
}