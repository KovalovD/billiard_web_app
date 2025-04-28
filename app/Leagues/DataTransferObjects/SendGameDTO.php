<?php

namespace App\Leagues\DataTransferObjects;

use App\Core\DataTransferObjects\BaseDTO;
use App\Core\Models\User;
use App\Leagues\Models\League;

class SendGameDTO extends BaseDTO
{
    public User $sender;
    public User $receiver;
    public League $league;

    public ?string $stream_url = null;
    public ?string $details = null;
    public ?int $club_id = null;

}
