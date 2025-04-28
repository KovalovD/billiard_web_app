<?php

namespace App\Auth\DataTransferObjects;

use App\Core\DataTransferObjects\BaseDTO;

class LogoutDTO extends BaseDTO
{
    public string $deviceName;
}
