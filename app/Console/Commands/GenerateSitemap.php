<?php

namespace App\Console\Commands;

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Matches\Models\MultiplayerGame;
use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Models\Tournament;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap files';

    public function handle(): void
    {
        $sitemap = Sitemap::create();

        // Add static pages
        $sitemap->add(Url::create('/')
            ->setPriority(1.0)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        $sitemap->add(Url::create('/dashboard')
            ->setPriority(0.9)
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

        $sitemap->add(Url::create('/players')
            ->setPriority(0.9)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));

        // Add legal pages
        $sitemap->add(Url::create('/privacy-policy')
            ->setPriority(0.5)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));

        $sitemap->add(Url::create('/service-agreement')
            ->setPriority(0.5)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));

        // Add authentication pages
        $sitemap->add(Url::create('/login')
            ->setPriority(0.6)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY));

        $sitemap->add(Url::create('/register')
            ->setPriority(0.7)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY));

        // Add dynamic league pages
        League::whereNotNull('updated_at')->each(function (League $league) use ($sitemap) {
            // League detail page
            $sitemap->add(Url::create("/leagues/{$league->slug}")
                ->setLastModificationDate($league->updated_at)
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));

            // League multiplayer games index page
            $sitemap->add(Url::create("/leagues/{$league->slug}/multiplayer-games")
                ->setLastModificationDate($league->updated_at)
                ->setPriority(0.7)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        });

        // Add multiplayer game pages
        MultiplayerGame::with('league')->whereNotNull('updated_at')->each(function (MultiplayerGame $game) use (
            $sitemap,
        ) {
            $sitemap->add(Url::create("/leagues/{$game->league->slug}/multiplayer-games/{$game->slug}")
                ->setLastModificationDate($game->updated_at)
                ->setPriority(0.7)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        });

        // Add player pages
        User::whereNotNull('updated_at')
            ->where('is_active', true) // Only include approved users
            ->each(function (User $user) use ($sitemap) {
                $sitemap->add(Url::create("/players/{$user->slug}")
                    ->setLastModificationDate($user->updated_at)
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
            })
        ;

        // Add tournament pages
        Tournament::whereNotNull('updated_at')
            ->where('status', '!=', 'cancelled')
            ->each(function (Tournament $tournament) use ($sitemap) {
                $sitemap->add(Url::create("/tournaments/{$tournament->slug}")
                    ->setLastModificationDate($tournament->updated_at)
                    ->setPriority(0.8)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
            })
        ;

        // Add official rating pages
        OfficialRating::whereNotNull('updated_at')
            ->where('is_active', true)
            ->each(function (OfficialRating $rating) use ($sitemap) {
                $sitemap->add(Url::create("/official-ratings/{$rating->slug}")
                    ->setLastModificationDate($rating->updated_at)
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
            })
        ;

        // Write main sitemap
        $sitemap->writeToFile(public_path('sitemap.xml'));

        // Optional: Create a sitemap index if you have multiple sitemaps
        $this->createRobotsTxt();

        $this->info('Sitemap generated successfully!');
    }

    /**
     * Create or update robots.txt file
     */
    protected function createRobotsTxt(): void
    {
        $robotsContent = "User-agent: *\n";
        $robotsContent .= "Allow: /\n\n";

        // Disallow admin and profile areas
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /profile/\n";
        $robotsContent .= "Disallow: /api/\n";
        $robotsContent .= "Disallow: /widgets/\n\n";

        // Add sitemap location
        $robotsContent .= "Sitemap: ".config('app.url')."/sitemap.xml\n";

        file_put_contents(public_path('robots.txt'), $robotsContent);

        $this->info('robots.txt file updated!');
    }
}
