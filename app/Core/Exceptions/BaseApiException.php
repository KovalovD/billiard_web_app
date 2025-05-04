<?php

namespace App\Core\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class BaseApiException extends Exception
{
    protected int $statusCode = 500 {
        get: int {
            return $this->statusCode;
        }
    }
    protected string $errorCode = 'SERVER_ERROR' {
        get: string {
            return $this->errorCode;
        }
    }
    protected array $extras = [] {
        get: array {
            return $this->extras;
        }
    }

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
}
