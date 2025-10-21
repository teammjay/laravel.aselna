<?php

namespace App\Helpers;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ErrorLogger
{
    /**
     * Log an exception to the oydera_error_log table.
     *
     * @param \Throwable $e
     * @param int $errorSource
     */
    public static function log(\Throwable $e, $errorSource = 50)
    {
        $trace = $e->getTrace();
        $originClass = $trace[0]['class'] ?? null;
        $originMethod = $trace[0]['function'] ?? null;
        //$operation = $originClass && $originMethod ? "{$originClass}::{$originMethod}" : __METHOD__;
        //$hash = hash('sha256', $e->getMessage() . $e->getTraceAsString());

        $operation = null;

        foreach ($trace as $frame) {
            if (isset($frame['class'])
                && !str_starts_with($frame['class'], 'Illuminate\\')
                && !str_starts_with($frame['class'], 'Symfony\\')
            ) {
                $operation = ($frame['class'] ?? '') . '::' . ($frame['function'] ?? '');
                break;
            }
        }

        $operation = $operation ?? __METHOD__;

        try {
            ErrorLog::create([
                'id' => Str::uuid()->toString(),
                'update_at' => now(),
                'create_at' => now(),
                'store_id' => null,
                'error_level' => 20, // 20 = Error, customize as needed
                'error_source' => 50, // 50 = System, 51 = Marketplace, etc.
                'operation' => $operation,
                'status' => 0,
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        } catch (\Throwable $ex) {
            // fallback to Laravel log if DB fails
            Log::error('Failed to log error: '.$ex->getMessage());
        }
    }
}
