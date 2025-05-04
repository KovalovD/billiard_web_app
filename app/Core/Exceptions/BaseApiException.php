<?php

namespace App\Core\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class BaseApiException extends Exception
{
    protected int $statusCode = 500;
    protected string $errorCode = 'SERVER_ERROR';
    protected array $extras = [];

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code'    => $this->errorCode,
                'message' => $this->getMessage(),
                'extras'  => $this->extras,
            ],
        ], $this->statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getExtras(): array
    {
        return $this->extras;
    }
}
