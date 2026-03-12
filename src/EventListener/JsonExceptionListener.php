<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class JsonExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only handle exceptions for API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        
        // Determine status code
        $statusCode = 500;
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        } elseif ($exception instanceof AuthenticationException) {
            $statusCode = 401;
        } elseif ($exception instanceof AccessDeniedException) {
            $statusCode = 403;
        }

        // Prepare error response
        $responseData = [
            'error' => true,
            'message' => $exception->getMessage(),
            'code' => $statusCode,
        ];

        // In dev environment, add more details
        if ($_ENV['APP_ENV'] === 'dev') {
            $responseData['debug'] = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        // Create JSON response
        $response = new JsonResponse($responseData, $statusCode);
        
        // Set the response
        $event->setResponse($response);
    }
}
