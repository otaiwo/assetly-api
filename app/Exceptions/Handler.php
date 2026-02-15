<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    // ... other code like $dontReport, etc.

    public function render($request, Throwable $exception)
    {
        // Handle model not found
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        }

        // Handle unauthorized actions
        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // fallback to default
        return parent::render($request, $exception);
        
        // Handle validation errors
        if ($exception instanceof ValidationException) {
    return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $exception->errors(),
    ], 422);
}
    }
}
