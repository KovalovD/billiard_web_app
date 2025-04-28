<?php

namespace App\Auth\DataTransferObjects;

use App\Core\DataTransferObjects\BaseDTO;

class LoginDTO extends BaseDTO
{
    public string $email;
    public string $password;
    public string $deviceName = 'web';
}
