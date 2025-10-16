<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_produit' => 'required|string',
            'prix' => 'required|numeric',
            'categorie' => 'required|string',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ];
    }

    public function messages(): array
    {
        return [
            'image.mimes' => 'Format d\'image non supporté (JPG, PNG, WEBP uniquement)',
            'image.max' => 'Taille maximale de l\'image: 2MB'
        ];
    }
}