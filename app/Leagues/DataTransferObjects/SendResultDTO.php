<?php

namespace App\Leagues\DataTransferObjects;

use App\Core\DataTransferObjects\BaseDTO;
use App\Matches\Models\MatchGame;

class SendResultDTO extends BaseDTO
{
    public int $first_user_score;
    public int $second_user_score;

    public MatchGame $matchGame;
}
