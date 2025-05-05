<?php

namespace App\Auth\DataTransferObjects;

use App\Core\DataTransferObjects\BaseDTO;

class RegisterDTO extends BaseDTO
{
    public string $firstname;
    public string $lastname;
    public string $email;
    public string $phone;
    public string $password;
}
