<?php

namespace App\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleController
{
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

    public function getLocale(): JsonResponse
    {
        $locale = Session::get('locale');
        App::setLocale($locale);

        return response()->json([
            'current_locale'    => $locale,
            'available_locales' => config('app.available_locales'),
            'translations'      => $this->getTranslations($locale),
        ]);
    }

    private function getTranslations(string $locale): array
    {
        $translations = [];

        // Загружаем все файлы переводов для текущей локали
        $translationFiles = [
            'common',
            'auth',
            'navigation',
            'leagues',
            'tournaments',
            'players',
            'games',
            'profile',
            'validation',
        ];

        foreach ($translationFiles as $file) {
            $filePath = resource_path("lang/$locale/$file.php");
            if (file_exists($filePath)) {
                $translations[$file] = include $filePath;
            }
        }

        return $translations;
    }
}
