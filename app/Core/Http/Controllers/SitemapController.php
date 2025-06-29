<?php

namespace App\Core\Http\Controllers;

use App\Leagues\Models\League;
use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Models\Tournament;
use Illuminate\Http\Response;

class SitemapController
{
    public function index(): Response
    {
        $sitemaps = [
            route('sitemap.static'),
            route('sitemap.leagues'),
            route('sitemap.tournaments'),
            route('sitemap.ratings'),
        ];

        return response()
            ->view('sitemap.index', compact('sitemaps'))
            ->header('Content-Type', 'text/xml')
        ;
    }

    public function static(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => url('/leagues'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => url('/tournaments'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => url('/official-ratings'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => url('/dashboard'), 'priority' => '0.8', 'changefreq' => 'daily'],
        ];

        return response()
            ->view('sitemap.static', compact('urls'))
            ->header('Content-Type', 'text/xml')
        ;
    }

    public function leagues(): Response
    {
        $leagues = League::where('is_active', true)
            ->select('id', 'updated_at')
            ->get()
            ->map(function ($league) {
                return [
                    'loc'        => url("/leagues/{$league->id}"),
                    'lastmod'    => $league->updated_at->toIso8601String(),
                    'priority'   => '0.8',
                    'changefreq' => 'weekly',
                ];
            })
        ;

        return response()
            ->view('sitemap.urls', ['urls' => $leagues])
            ->header('Content-Type', 'text/xml')
        ;
    }

    public function tournaments(): Response
    {
        $tournaments = Tournament::where('status', '!=', 'cancelled')
            ->select('id', 'updated_at')
            ->get()
            ->map(function ($tournament) {
                return [
                    'loc'        => url("/tournaments/{$tournament->id}"),
                    'lastmod'    => $tournament->updated_at->toIso8601String(),
                    'priority'   => '0.8',
                    'changefreq' => 'weekly',
                ];
            })
        ;

        return response()
            ->view('sitemap.urls', ['urls' => $tournaments])
            ->header('Content-Type', 'text/xml')
        ;
    }

    public function ratings(): Response
    {
        $ratings = OfficialRating::where('is_active', true)
            ->select('id', 'updated_at')
            ->get()
            ->map(function ($rating) {
                return [
                    'loc'        => url("/official-ratings/{$rating->id}"),
                    'lastmod'    => $rating->updated_at->toIso8601String(),
                    'priority'   => '0.7',
                    'changefreq' => 'monthly',
                ];
            })
        ;

        return response()
            ->view('sitemap.urls', ['urls' => $ratings])
            ->header('Content-Type', 'text/xml')
        ;
    }
}
