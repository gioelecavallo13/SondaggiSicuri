<?php

namespace App\Http\Controllers;

use App\Services\ProfilePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('profile.show', [
            'user' => auth()->user(),
        ]);
    }

    public function uploadPhoto(Request $request, ProfilePhotoService $profilePhotos): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
        ], [
            'photo.required' => 'Seleziona un’immagine da caricare.',
            'photo.mimes' => 'Formato non valido. Sono accettati JPEG (JPG), PNG, WebP o GIF.',
            'photo.max' => 'Il file supera la dimensione massima consentita (2 MB).',
        ]);

        $user = $request->user();
        $path = $profilePhotos->storeReplacingPrevious($user, $validated['photo']);
        $user->forceFill(['foto_profilo' => $path])->save();

        return response()->json([
            'url' => $profilePhotos->publicUrl($path),
        ]);
    }
}
