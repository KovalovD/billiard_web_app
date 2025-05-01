<?php

namespace App\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ErrorController extends Controller
{
    /**
     * Handle 404 Not Found errors
     */
    public function notFound(Request $request): JsonResponse|Response
    {
        return $this->show($request, 404);
    }

    public function show(
        Request $request,
        int $status = 404,
        ?string $customMessage = null,
        ?string $customDescription = null,
    ): JsonResponse|Response {
        $message = $customMessage ?? $this->getStatusMessage($status);
        $description = $customDescription ?? $this->getErrorDescription($status);

        return Inertia::render('Errors/Index', [
            'status'      => $status,
            'message'     => $message,
            'description' => $description,
        ])->toResponse($request)->setStatusCode($status);
    }

    /**
     * Get standard message for a status code
     */
    private function getStatusMessage(int $status): string
    {
        return match ($status) {
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            419 => 'Page Expired',
            429 => 'Too Many Requests',
            500 => 'Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }

    /**
     * Get standard description for a status code
     */
    private function getErrorDescription(int $status): string
    {
        return match ($status) {
            401 => 'You are not authorized to access this page. Please log in and try again.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The page you are looking for does not exist or has been moved.',
            419 => 'Your session has expired. Please refresh and try again.',
            429 => 'You have made too many requests. Please wait a moment and try again.',
            500 => 'Something went wrong on our server. We are working to fix the issue.',
            503 => 'The service is temporarily unavailable. Please try again later.',
            default => 'An error occurred.',
        };
    }

    /**
     * Handle 403 Forbidden errors
     */
    public function forbidden(Request $request): JsonResponse|Response
    {
        return $this->show($request, 403);
    }

    /**
     * Handle 500 Server errors
     */
    public function serverError(Request $request): JsonResponse|Response
    {
        return $this->show($request, 500);
    }
}
