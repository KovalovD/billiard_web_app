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
    public Carbon $started_at;
    public Carbon $finished_at;
    public int $start_rating;
    public string $rating_change_for_winners_rule;
    public string $rating_change_for_losers_rule;
}
