<?php

namespace App\Console\Commands;

use App\Leagues\Models\League;
use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Models\Tournament;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap files';

    public function handle()
    {
        $sitemap = Sitemap::create();

        // Add static pages
        $sitemap->add(Url::create('/')
            ->setPriority(1.0)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        $sitemap->add(Url::create('/leagues')
            ->setPriority(0.9)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        $sitemap->add(Url::create('/tournaments')
            ->setPriority(0.9)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        $sitemap->add(Url::create('/official-ratings')
            ->setPriority(0.9)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));

        // Add dynamic pages
        League::whereNotNull('updated_at')->each(function (League $league) use ($sitemap) {
            $sitemap->add(Url::create("/leagues/{$league->id}")
                ->setLastModificationDate($league->updated_at)
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        });

        Tournament::whereNotNull('updated_at')->where('status', '!=', 'cancelled')->each(function (
            Tournament $tournament,
        ) use ($sitemap) {
            $sitemap->add(Url::create("/tournaments/{$tournament->id}")
                ->setLastModificationDate($tournament->updated_at)
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        });

        OfficialRating::whereNotNull('updated_at')->where('is_active', true)->each(function (OfficialRating $rating) use
        (
            $sitemap,
        ) {
            $sitemap->add(Url::create("/official-ratings/{$rating->id}")
                ->setLastModificationDate($rating->updated_at)
                ->setPriority(0.7)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully!');
    }
}
