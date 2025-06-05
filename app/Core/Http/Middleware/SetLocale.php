<?php

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Получаем локаль из сессии, заголовков или используем дефолтную
        $locale = $this->getLocale($request);

        if (in_array($locale, config('app.available_locales'), true)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    private function getLocale(Request $request): string
    {
        // 1. Проверяем параметр запроса
        if ($request->has('locale') && in_array($request->get('locale'), config('app.available_locales'), true)) {
            return $request->get('locale');
        }

        // 2. Проверяем сессию
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 3. Проверяем заголовок Accept-Language
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $preferredLanguage = substr($acceptLanguage, 0, 2);
            if (in_array($preferredLanguage, config('app.available_locales'), true)) {
                return $preferredLanguage;
            }
        }

        // 4. Возвращаем дефолтную локаль
        return config('app.locale');
    }
}
