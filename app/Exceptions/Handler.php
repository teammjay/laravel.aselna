<?php

namespace App\Exceptions;

use App\Helpers\ErrorLogger;
use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\ErrorLog;

class Handler extends ExceptionHandler
{
    // Don’t log these exceptions (optional)
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception)
    {
        try {

            ErrorLogger::log($exception, 50);

        } catch (\Exception $e) {
            // If logging fails, fall back to Laravel log
            Log::error('Error logging exception: ' . $e->getMessage());
        }

        // Call Laravel’s default reporting
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Return JSON if it’s an API request
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 500);
        }

        // Otherwise, use default Laravel error page
        return parent::render($request, $exception);
    }
}
