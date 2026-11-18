<?php

namespace App\Utils;

use App\Models\SysFile;
use App\Services\CriticalErrorService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FileUtility
{
    /**
     * Write a generated file (feed, result feed, ...) so that it stays writable by
     * BOTH the web user (PHP-FPM) and the queue/scheduler user.
     *
     * Feeds are written from web requests and from queue workers. If those run as
     * different OS users (e.g. workers as root), a file/dir created by one is
     * read-only for the other and the next write fails with
     * "file_put_contents(...): Failed to open stream: Permission denied".
     */
    public static function saveToFile(string $filePath, string $fileContent): void
    {
        $directory = dirname($filePath);
        self::ensureSharedDirectory($directory);

        // An existing file we can't write (created by another user) can still be
        // replaced if the directory is writable: drop it and write a fresh one.
        if (is_file($filePath) && !is_writable($filePath) && is_writable($directory)) {
            @unlink($filePath);
        }

        File::put($filePath, $fileContent);
        self::shareOwnership($filePath, 0664);
    }

    /**
     * Create the directory (recursively) and make every newly created level
     * group-writable with setgid, so files inside inherit the shared group.
     */
    public static function ensureSharedDirectory(string $directory): void
    {
        if (File::isDirectory($directory)) {
            return;
        }

        // Find the first missing ancestor so we only touch dirs we create.
        $missing = [];
        for ($dir = $directory; !File::isDirectory($dir) && $dir !== dirname($dir); $dir = dirname($dir)) {
            $missing[] = $dir;
        }

        File::ensureDirectoryExists($directory, 0775, true);

        foreach ($missing as $dir) {
            self::shareOwnership($dir, 02775);
        }
    }

    /**
     * Best effort: set mode, and when running as root, hand the path to the app
     * user/group. Never throws — a permission tweak must not fail the feed.
     */
    private static function shareOwnership(string $path, int $mode): void
    {
        if (app()->environment('local')) {
            return;
        }

        // chmod only succeeds for the owner (or root); ignore otherwise.
        @chmod($path, $mode);

        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $owner = config('filesystems.app_file_owner');
            $group = config('filesystems.app_file_group');
            if ($owner) {
                @chown($path, $owner);
            }
            if ($group) {
                @chgrp($path, $group);
            }
        }
    }

    public static function saveImage($imagePath, $localPath, $fileName)
    {
//        if (env('APP_ENV') === 'local') {
//            return self::saveImageToLocal($imagePath, $localPath, $fileName);
//        }

        try {
            Log::info('FileImageSave: ' . $imagePath);
            $storagePath = storage_path('app/public/' . $localPath . '/' . $fileName);
            // create folder if not exists
            File::ensureDirectoryExists(dirname($storagePath));
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
        }catch (\Throwable $e) {

            /*CriticalErrorService::report('Save Image Failed (System File)', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);*/
        }
        return null;

    }

    public static function createDirectoryIfNotExists(string $directory)
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
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
        try {
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
        }catch (\Throwable $e) {

            /*CriticalErrorService::report('Upload Image Failed (System File)', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);*/

            Log::error('Error: ' . $e->getMessage());

        }
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
