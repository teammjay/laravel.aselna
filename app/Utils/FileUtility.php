<?php

namespace App\Utils;

use Illuminate\Support\Facades\File;

class FileUtility
{
    public static function saveToFile(string $filePath, string $fileContent): void
    {
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $fileContent);
    }
}
