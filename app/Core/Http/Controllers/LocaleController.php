<?php

namespace App\Core\Http\Controllers;

use File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleController
{
    public function getLocale(): JsonResponse
    {
        $locale = Session::get('locale') ?: 'UK';
        App::setLocale($locale);

        return response()->json([
            'current_locale'    => $locale,
            'available_locales' => config('app.available_locales'),
            'translations'      => $this->getTranslations($locale),
        ]);
    }

    public function setLocale(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:'.implode(',', config('app.available_locales')),
        ]);

        $locale = $request->input('locale');

        App::setLocale($locale);
        Session::put('locale', $locale);

        return response()->json([
            'success' => true,
            'locale'  => $locale,
            'message' => __('Locale changed successfully'),
        ]);
    }

    private function getTranslations(string $locale): array
    {
        $translations = [];

        $translationFiles = File::allFiles(resource_path("lang/$locale"));   // Collection из SplFileInfo
        foreach ($translationFiles as $file) {
            $fileName = $file->getFilenameWithoutExtension();
            $filePath = resource_path("lang/$locale/$fileName.php");
            if (file_exists($filePath)) {
                $translations[$fileName] = include $filePath;
            }
        }

        return $translations;
    }
}
