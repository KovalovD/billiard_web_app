<?php

namespace App\Core\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): JsonResponse
    {
        // Handle custom API exceptions
        if ($e instanceof BaseApiException) {
            return $e->render();
        }

        // Handle Laravel validation exceptions
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return response()->json([
                'error' => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'extras'  => [
                        'errors' => $e->errors(),
                    ],
                ],
            ], 422);
        }

        // Handle 404s
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'error' => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Resource not found',
                ],
            ], 404);
        }

        // Generic error handler for production
        if (!config('app.debug')) {
            return response()->json([
                'error' => [
                    'code'    => 'SERVER_ERROR',
                    'message' => 'An error occurred. Please try again.',
                ],
            ], 500);
        }

        // Return detailed error in debug mode
        return response()->json([
            'error' => [
                'code'    => 'SERVER_ERROR',
                'message' => $e->getMessage(),
                'trace'   => $e->getTrace(),
            ],
        ], 500);
    }
}
