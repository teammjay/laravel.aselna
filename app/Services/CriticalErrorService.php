<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class CriticalErrorService
{
    public static function handle(Throwable $e, $context = null, $extra = [])
    {
        $errorData = [
            'error_id' => uniqid('critical_', true),
            'timestamp' => now()->toDateTimeString(),
            'context' => $context,
            'type' => 'Throwable',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'extra' => $extra,
        ];

        self::process($errorData);
    }

    public static function report($message, $context = null, $extra = [])
    {
        $errorData = [
            'error_id' => uniqid('critical_', true),
            'timestamp' => now()->toDateTimeString(),
            'context' => $context,
            'type' => 'Manual',
            'message' => $message,
            'extra' => $extra,
        ];

        self::process($errorData);
    }

    protected static function process($errorData)
    {
        // Log to file
        Log::channel('critical')->error('Critical Error', $errorData);

        //  storOptionallye to DB
        // \App\Models\CriticalError::create($errorData);

        /* Notification*/

    }
}
