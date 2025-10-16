<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    public function uploadOrdonnance(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs(config('upload.ordonnances.path'), $filename, 'public');
    }

    public function uploadProduitImage(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs(config('upload.produits.path'), $filename, 'public');
    }

    public function deleteFile(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}