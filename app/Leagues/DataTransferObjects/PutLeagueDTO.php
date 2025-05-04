<?php

namespace App\Leagues\DataTransferObjects;

use App\Core\DataTransferObjects\BaseDTO;
use Carbon\Carbon;

class PutLeagueDTO extends BaseDTO
{
    public string $name;
    public ?int $game_id;
    public ?string $picture;
    public ?string $details;
    public bool $has_rating;
    public ?Carbon $started_at;
    public ?Carbon $finished_at;
    public int $start_rating;
    public string $rating_change_for_winners_rule;
    public string $rating_change_for_losers_rule;
    public int $max_players;
    public int $max_score;
    public int $invite_days_expire;

    public static function prepareDataBeforeCreation(array $array): array
    {
        // Convert date strings to Carbon instances
        if (isset($array['started_at']) && $array['started_at']) {
            $array['started_at'] = Carbon::parse($array['started_at']);
        }
        if (isset($array['finished_at']) && $array['finished_at']) {
            $array['finished_at'] = Carbon::parse($array['finished_at']);
        }

        // Ensure has_rating is boolean
        if (isset($array['has_rating'])) {
            $array['has_rating'] = (bool) $array['has_rating'];
        } else {
            $array['has_rating'] = true;
        }

        // Parse JSON strings for rating rules
        if (isset($array['rating_change_for_winners_rule']) && is_string($array['rating_change_for_winners_rule'])) {
            $array['rating_change_for_winners_rule'] = json_encode(json_decode($array['rating_change_for_winners_rule'],
                true));
        }
        if (isset($array['rating_change_for_losers_rule']) && is_string($array['rating_change_for_losers_rule'])) {
            $array['rating_change_for_losers_rule'] = json_encode(json_decode($array['rating_change_for_losers_rule'],
                true));
        }

        return $array;
    }
}
