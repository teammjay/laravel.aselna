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

        if (env('APP_ENV') !== 'local') {
            chown($filePath, 'aselna');
            chgrp($filePath, 'psacln');
        }
        // chmod($filePath, 0777);
    }

    public static function saveImage($imagePath, $localPath, $fileName)
    {
//        if (env('APP_ENV') === 'local') {
//            return self::saveImageToLocal($imagePath, $localPath, $fileName);
//        }
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

    public static function readFromFile(string $public_path)
    {
        return File::get($public_path);
    }

    public static function uploadImage($store, $imagePath, $path)
    {
        if (env('APP_ENV') == 'local') {
            Log::info("Local environment detected, skipping image upload.");
            return '';
        }
        $storeFileSystem = $store->fileSystem;
        //Log::info("File system :: " . json_encode($storeFileSystem));
        $host = $storeFileSystem->host;
        $port = $storeFileSystem->port ?? 21;
        $loginId = $storeFileSystem->login_id;
        $password = $storeFileSystem->password;
        $rootPath = $storeFileSystem->root_path;
        $localFilePath = storage_path($imagePath);
        Log::info("Local File Path :: " . $localFilePath);

        $ftpConn = ftp_connect($host, $port);
        if (!$ftpConn) {
            Log::info("Could not connect to FTP server: $host");
        }

        $login = ftp_login($ftpConn, $loginId, $password);
        if (!$login) {
            ftp_close($ftpConn);
            Log::info("Could not login to FTP server with provided credentials.");
        }

        ftp_pasv($ftpConn, true);

        $remoteFilePath = $rootPath . $path;
        Log::info("Remote Path :: " . $remoteFilePath);

        self::createFtpDirectory($ftpConn, dirname($remoteFilePath));

        //Upload the file
        if (ftp_put($ftpConn, $remoteFilePath, $localFilePath, FTP_BINARY)) {
            //echo "File uploaded successfully to $remoteFilePath";
            return $storeFileSystem->url_base_path . $path;
        } else {
            //echo "Error uploading file.";
        }

        // Close FTP connection
        ftp_close($ftpConn);
    }

    public static function createFtpDirectory($ftpConn, $dirPath)
    {
        $parts = explode('/', $dirPath);
        $currentDir = '';

        foreach ($parts as $part) {
            if (empty($part)) continue;
            $currentDir .= "/$part";

            if (!@ftp_chdir($ftpConn, $currentDir)) { // Check if directory exists
                ftp_mkdir($ftpConn, $currentDir); // Create directory if it does not exist
            }
        }
    }
}
