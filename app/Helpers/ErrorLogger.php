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
    public static function log(\Throwable $e, $operation = null, $extraInfo = [], $errorLevel='20', $errorSource = 50)
    {
        $trace = $e->getTrace();
//        $originClass = $trace[0]['class'] ?? null;
//        $originMethod = $trace[0]['function'] ?? null;
//        $operation = $originClass && $originMethod ? "{$originClass}::{$originMethod}" : __METHOD__;
        $hash = hash('sha256', $e->getMessage() . $e->getTraceAsString());

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

            $existing = ErrorLog::where('hash_string', $hash)->first();
            $message  = $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine();

            if ($existing) {
                $existing->increment('error_count');
                $existing->update(['updated_at' => now()]);
            } else {
                ErrorLog::create([
                    'id' => Str::uuid()->toString(),
                    'update_at' => now(),
                    'create_at' => now(),
                    'store_id' => $extraInfo['store_id'] ?? NULL,
                    'error_level' => $errorLevel, // 20 = Error, customize as needed
                    'error_source' => $errorSource, // 50 = System, 51 = Marketplace, etc.
                    'operation' => $operation,
                    'status' => 0,
                    'message' => $message,
                    'stack_trace' => $e->getTraceAsString(),
                    'hash_string' => $hash,
                    'error_count' => 1,
                ]);
            }
        } catch (\Throwable $ex) {
            // fallback to Laravel log if DB fails
            Log::error('Failed to log error: '.$ex->getMessage());
        }
    }

    /**
     * Log a plain message meant for the user, without the file / line / trace noise of log().
     * Repeats of the same message for the same store bump error_count instead of adding rows.
     */
    public static function logMessage(string $message, string $operation, ?string $storeId = null, $errorLevel = '20', $errorSource = 50)
    {
        $hash = hash('sha256', $message . $operation . $storeId);

        try {
            $existing = ErrorLog::where('hash_string', $hash)->first();

            if ($existing) {
                $existing->increment('error_count');
                $existing->update(['updated_at' => now(), 'status' => 0]);
            } else {
                ErrorLog::create([
                    'id' => Str::uuid()->toString(),
                    'update_at' => now(),
                    'create_at' => now(),
                    'store_id' => $storeId,
                    'error_level' => $errorLevel,
                    'error_source' => $errorSource,
                    'operation' => $operation,
                    'status' => 0,
                    'message' => $message,
                    'stack_trace' => null,
                    'hash_string' => $hash,
                    'error_count' => 1,
                ]);
            }
        } catch (\Throwable $ex) {
            Log::error('Failed to log error: '.$ex->getMessage());
        }
    }
}