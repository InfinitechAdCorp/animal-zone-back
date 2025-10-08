<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
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

    /**
     * Handle unauthenticated exceptions for API and web routes.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Always return JSON for API calls, avoid redirecting to a non-existent login route
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // If you have a web login page, change this to your actual login route
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
