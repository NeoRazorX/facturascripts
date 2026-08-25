<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

const FS_RESTORE_ARCHIVE = 'CORE.zip';
const FS_RESTORE_PACKAGE_FOLDER = 'facturascripts';
const FS_RESTORE_URL = 'https://facturascripts.com/DownloadBuild/1/stable';

function restoreMessage(string $message, bool $error = false): void
{
    if (!headers_sent()) {
        http_response_code($error ? 500 : 200);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $title = $error ? 'FacturaScripts restoration error' : 'FacturaScripts restored successfully';
    echo '<!doctype html><html lang="en"><head><meta charset="UTF-8"><title>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</title></head><body><h1>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</h1><p>'
        . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        . '</p>';

    if (!$error) {
        echo '<p><a href="./">Click here to continue</a>.</p>';
    }

    echo '</body></html>';
    exit($error ? 1 : 0);
}

function restorePathExists(string $path): bool
{
    return file_exists($path) || is_link($path);
}

function restoreDeletePath(string $path): bool
{
    if (is_link($path)) {
        return @unlink($path) || @rmdir($path);
    }

    if (is_file($path)) {
        return @unlink($path);
    }

    if (!is_dir($path)) {
        return !restorePathExists($path);
    }

    $files = scandir($path);
    if (false === $files) {
        return false;
    }

    foreach (array_diff($files, ['.', '..']) as $file) {
        if (!restoreDeletePath($path . DIRECTORY_SEPARATOR . $file)) {
            return false;
        }
    }

    return @rmdir($path);
}

function restoreDownloadArchive(string $archivePath): void
{
    $tempPath = $archivePath . '.part';
    if (!restoreDeletePath($tempPath)) {
        throw new RuntimeException('Unable to remove the previous temporary download.');
    }

    $stream = @fopen($tempPath, 'wb');
    if (false === $stream) {
        throw new RuntimeException('Unable to create the temporary download file.');
    }

    $curl = curl_init(FS_RESTORE_URL);
    if (false === $curl) {
        fclose($stream);
        restoreDeletePath($tempPath);
        throw new RuntimeException('Unable to initialize the download.');
    }

    $configured = curl_setopt_array($curl, [
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FAILONERROR => true,
        CURLOPT_FILE => $stream,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_USERAGENT => 'FacturaScripts Restore',
    ]);

    if (!$configured) {
        curl_close($curl);
        fclose($stream);
        restoreDeletePath($tempPath);
        throw new RuntimeException('Unable to configure the download.');
    }

    $downloaded = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    fclose($stream);

    $fileSize = filesize($tempPath);
    if (false === $downloaded || $status < 200 || $status >= 300 || false === $fileSize || $fileSize === 0) {
        restoreDeletePath($tempPath);
        throw new RuntimeException('Unable to download CORE.zip. HTTP ' . $status . '. ' . $error);
    }

    $zip = new ZipArchive();
    $zipStatus = $zip->open($tempPath, ZipArchive::CHECKCONS);
    if (true !== $zipStatus) {
        restoreDeletePath($tempPath);
        throw new RuntimeException('The downloaded CORE.zip is invalid. ZIP error ' . $zipStatus . '.');
    }
    $zip->close();

    if (restorePathExists($archivePath) && !restoreDeletePath($archivePath)) {
        restoreDeletePath($tempPath);
        throw new RuntimeException('Unable to replace the previous CORE.zip.');
    }

    if (!@rename($tempPath, $archivePath)) {
        restoreDeletePath($tempPath);
        throw new RuntimeException('Unable to save CORE.zip.');
    }
}

function restoreValidateArchive(ZipArchive $zip): void
{
    $prefix = FS_RESTORE_PACKAGE_FOLDER . '/';
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = $zip->getNameIndex($index);
        if (false === $name || strpos($name, "\0") !== false) {
            throw new RuntimeException('CORE.zip contains an invalid entry.');
        }

        $normalized = str_replace('\\', '/', $name);
        $parts = explode('/', $normalized);
        $insidePackage = $normalized === FS_RESTORE_PACKAGE_FOLDER
            || $normalized === $prefix
            || strpos($normalized, $prefix) === 0;
        if (!$insidePackage) {
            throw new RuntimeException('CORE.zip contains files outside the expected package folder.');
        }

        if (in_array('..', $parts, true) || str_starts_with($normalized, '/')) {
            throw new RuntimeException('CORE.zip contains an unsafe path.');
        }

        $operatingSystem = 0;
        $attributes = 0;
        if ($zip->getExternalAttributesIndex($index, $operatingSystem, $attributes)) {
            $fileType = ($attributes >> 16) & 0170000;
            if ($fileType === 0120000) {
                throw new RuntimeException('CORE.zip contains an unsupported symbolic link.');
            }
        }
    }
}

function restoreValidatePackage(string $packagePath): void
{
    $requiredDirectories = ['Core', 'node_modules', 'vendor'];
    foreach ($requiredDirectories as $directory) {
        $path = $packagePath . DIRECTORY_SEPARATOR . $directory;
        if (!is_dir($path) || is_link($path)) {
            throw new RuntimeException('The package does not contain a valid ' . $directory . ' folder.');
        }
    }

    $requiredFiles = ['index.php', 'replace_index_to_restore.php'];
    foreach ($requiredFiles as $file) {
        $path = $packagePath . DIRECTORY_SEPARATOR . $file;
        if (!is_file($path) || is_link($path)) {
            throw new RuntimeException('The package does not contain a valid ' . $file . ' file.');
        }
    }
}

function restoreRollback(string $backupPath, array $states): array
{
    $errors = [];
    foreach (array_reverse(array_keys($states)) as $name) {
        $destination = __DIR__ . DIRECTORY_SEPARATOR . $name;
        $backup = $backupPath . DIRECTORY_SEPARATOR . $name;

        if ($states[$name]['installed'] && !restoreDeletePath($destination)) {
            $errors[] = 'Unable to remove the new ' . $name . '.';
            continue;
        }

        if ($states[$name]['backedUp'] && !@rename($backup, $destination)) {
            $errors[] = 'Unable to restore the previous ' . $name . '.';
        }
    }

    return $errors;
}

function restoreInstallPackage(string $packagePath, string $backupPath): void
{
    if (!@mkdir($backupPath, 0755, true)) {
        throw new RuntimeException('Unable to create the restoration backup folder.');
    }

    $targets = ['Core', 'node_modules', 'vendor', 'index.php', 'replace_index_to_restore.php'];
    $states = [];

    try {
        foreach ($targets as $name) {
            $source = $packagePath . DIRECTORY_SEPARATOR . $name;
            $destination = __DIR__ . DIRECTORY_SEPARATOR . $name;
            $backup = $backupPath . DIRECTORY_SEPARATOR . $name;
            $states[$name] = ['backedUp' => false, 'installed' => false];

            if (restorePathExists($destination)) {
                if (!@rename($destination, $backup)) {
                    throw new RuntimeException('Unable to back up ' . $name . '.');
                }
                $states[$name]['backedUp'] = true;
            }

            if (!@rename($source, $destination)) {
                throw new RuntimeException('Unable to install ' . $name . '.');
            }
            $states[$name]['installed'] = true;
        }
    } catch (Throwable $exception) {
        $rollbackErrors = restoreRollback($backupPath, $states);
        $message = $exception->getMessage();
        if (!empty($rollbackErrors)) {
            $message .= ' Rollback errors: ' . implode(' ', $rollbackErrors)
                . ' Backup preserved at ' . $backupPath . '.';
        } elseif (!restoreDeletePath($backupPath)) {
            $message .= ' The empty backup folder could not be removed: ' . $backupPath . '.';
        }

        throw new RuntimeException($message, 0, $exception);
    }
}

// This file must replace index.php before it can perform a restoration.
if (basename(__FILE__) !== 'index.php') {
    restoreMessage('Remove index.php and rename this file to index.php.', true);
}

if (version_compare(PHP_VERSION, '8.1.0') < 0) {
    restoreMessage('FacturaScripts requires PHP 8.1.0 or newer.', true);
}

if (!class_exists('ZipArchive')) {
    restoreMessage('FacturaScripts restoration requires the PHP ZIP extension.', true);
}

if (!function_exists('curl_init')) {
    restoreMessage('FacturaScripts restoration requires the PHP cURL extension.', true);
}

@set_time_limit(0);
ignore_user_abort(true);

$lockPath = __DIR__ . DIRECTORY_SEPARATOR . 'restore.lock';
$lock = @fopen($lockPath, 'c');
if (false === $lock) {
    restoreMessage('Unable to create the restoration lock.', true);
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    restoreMessage('Another restoration process is already running. Try again in a few minutes.', true);
}

$archivePath = __DIR__ . DIRECTORY_SEPARATOR . FS_RESTORE_ARCHIVE;
$token = bin2hex(random_bytes(8));
$stagingPath = __DIR__ . DIRECTORY_SEPARATOR . 'restore-staging-' . $token;
$backupPath = __DIR__ . DIRECTORY_SEPARATOR . 'restore-backup-' . $token;

try {
    if (restorePathExists($archivePath)) {
        $zip = new ZipArchive();
        $zipStatus = $zip->open($archivePath, ZipArchive::CHECKCONS);
        if (true !== $zipStatus) {
            if (!restoreDeletePath($archivePath)) {
                throw new RuntimeException('The existing CORE.zip is invalid and cannot be removed.');
            }
            restoreDownloadArchive($archivePath);
        } else {
            $zip->close();
        }
    } else {
        restoreDownloadArchive($archivePath);
    }

    $zip = new ZipArchive();
    $zipStatus = $zip->open($archivePath, ZipArchive::CHECKCONS);
    if (true !== $zipStatus) {
        throw new RuntimeException('Unable to open CORE.zip. ZIP error ' . $zipStatus . '.');
    }

    restoreValidateArchive($zip);

    if (!@mkdir($stagingPath, 0755, true)) {
        $zip->close();
        throw new RuntimeException('Unable to create the restoration staging folder.');
    }

    $previousUmask = umask(0022);
    try {
        $extracted = $zip->extractTo($stagingPath);
    } finally {
        umask($previousUmask);
        $zip->close();
    }
    if (!$extracted) {
        throw new RuntimeException('Unable to extract CORE.zip.');
    }

    $packagePath = $stagingPath . DIRECTORY_SEPARATOR . FS_RESTORE_PACKAGE_FOLDER;
    restoreValidatePackage($packagePath);
    restoreInstallPackage($packagePath, $backupPath);
} catch (Throwable $exception) {
    restoreDeletePath($stagingPath);
    flock($lock, LOCK_UN);
    fclose($lock);
    restoreMessage($exception->getMessage(), true);
}

$warnings = [];
if (!restoreDeletePath($backupPath)) {
    $warnings[] = 'The backup folder could not be removed: ' . $backupPath . '.';
}
if (!restoreDeletePath($stagingPath)) {
    $warnings[] = 'The staging folder could not be removed: ' . $stagingPath . '.';
}
if (!restoreDeletePath($archivePath)) {
    $warnings[] = 'CORE.zip could not be removed.';
}

flock($lock, LOCK_UN);
fclose($lock);

$message = 'The latest stable version has been installed.';
if (!empty($warnings)) {
    $message .= ' Cleanup warnings: ' . implode(' ', $warnings);
}

restoreMessage($message);
