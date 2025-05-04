<?php

namespace App\Core\Exceptions;

class ValidationException extends BaseApiException
{
    protected int $statusCode = 422;
    protected string $errorCode = 'VALIDATION_ERROR';

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->extras = [
            'errors' => $errors,
        ];
    }
}
