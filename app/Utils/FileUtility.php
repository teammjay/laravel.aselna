<?php

namespace App\Utils;

use App\Models\SysFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FileUtility
{
    public static function saveToFile(string $filePath, string $fileContent): void
    {
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $fileContent);
    }

    public static function saveImage($imagePath, $localPath, $fileName)
    {
        if (env('APP_ENV') === 'local') {
            return self::saveImageToLocal($imagePath, $localPath, $fileName);
        }
        Log::info('FileImageSave: ' . $imagePath);
        $storagePath = storage_path('app/public/' . $localPath . '/' . $fileName);
        $image = file_get_contents($imagePath);
        if ($image !== false) {
            file_put_contents($storagePath, $image);

            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);

            $fileSize = filesize($storagePath);

            $filedata = [
                'name' => $fileName,
                'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                'location' => $localPath . '/',
                'created_by' => 'admin',
            ];

            return SysFile::create($filedata);
        }
        return null;
    }

    private static function saveImageToLocal($imagePath, $localPath, $fileName)
    {
        Log::info('FileImageSave: ' . $imagePath);
        $filedata = [
            'name' => $fileName,
            'ext' => 'jpg', // Default to 'jpg' if missing
            'file_size' => 1, // Use 'Unknown' if not determined
            'location' => $localPath . '/',
            'created_by' => 'admin',
        ];

        return SysFile::create($filedata);
    }
}
