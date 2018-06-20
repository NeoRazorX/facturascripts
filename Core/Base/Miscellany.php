<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos García Gómez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base;

/**
 * Class Miscellany.
 * Mix of different things that at this time are no so specific to be in a concrete class.
 *
 * @source https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/misc.php
 *
 * @author Francesc Pineda Segarra <francesc.pineda@x-netdigital.com>
 */
class Miscellany
{
    const MARKER = 'FacturaScripts-generated handler, do not edit';
    const HTACCESS = \FS_FOLDER . '/.htaccess';

    /**
     * Extracts strings from between the BEGIN and END markers in the .htaccess file.
     *
     * @source https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/misc.php#L67
     *
     * @param string $fileName
     * @param string $marker
     *
     * @return array An array of strings from a file (.htaccess ) from between BEGIN and END markers.
     */
    public static function extractFromMarkers(string $fileName = self::HTACCESS, string $marker = self::MARKER): array
    {
        $result = [];
        if (!file_exists($fileName)) {
            return $result;
        }
        $markerData = explode(\PHP_EOL, file_get_contents($fileName));
        $state = false;
        foreach ($markerData as $markerLine) {
            if (false !== strpos($markerLine, '# END ' . $marker)) {
                $state = false;
            }
            if ($state) {
                $result[] = $markerLine;
            }
            if (false !== strpos($markerLine, '# BEGIN ' . $marker)) {
                $state = true;
            }
        }
        return $result;
    }

    /**
     * Inserts an array of strings into a file (.htaccess ), placing it between
     * BEGIN and END markers.
     *
     * Replaces existing marked info. Retains surrounding
     * data. Creates file if none exists.
     *
     * @source https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/misc.php#L106
     *
     * @param array|string $insertion The new content to insert.
     * @param string       $fileName  Filename to alter.
     * @param string       $marker    The marker to alter.
     *
     * @return bool True on write success, false on failure.
     */
    public static function insertWithMarkers($insertion, string $fileName = self::HTACCESS, string $marker = self::MARKER): bool
    {
        if (!file_exists($fileName)) {
            if (!is_writable(\dirname($fileName))) {
                return false;
            }
            if (!touch($fileName)) {
                return false;
            }
        } elseif (!is_writable($fileName)) {
            return false;
        }
        if (!\is_array($insertion)) {
            $insertion = explode(\PHP_EOL, $insertion);
        }
        $startMarker = '# BEGIN ' . $marker;
        $endMarker = '# END ' . $marker;
        $fp = fopen($fileName, 'rb+');
        if (!$fp) {
            return false;
        }
        // Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
        flock($fp, LOCK_EX);
        $lines = [];
        while (!feof($fp)) {
            $lines[] = rtrim(fgets($fp), "\r\n");
        }
        // Split out the existing file into the preceding lines, and those that appear after the marker
        $preLines = $postLines = $existingLines = [];
        $foundMarker = $foundEndMarker = false;
        foreach ($lines as $line) {
            if (!$foundMarker && false !== strpos($line, $startMarker)) {
                $foundMarker = true;
                continue;
            }
            if (!$foundEndMarker && false !== strpos($line, $endMarker)) {
                $foundEndMarker = true;
                continue;
            }
            if (!$foundMarker) {
                $preLines[] = $line;
            } elseif ($foundMarker && $foundEndMarker) {
                $postLines[] = $line;
            } else {
                $existingLines[] = $line;
            }
        }
        // Check to see if there was a change
        if ($existingLines === $insertion) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }

        // If it's true, is the old content version without the tags marker, we can remove it
        if (empty(\array_diff($insertion, $preLines))) {
            $preLines = [];
        }

        // Generate the new file data
        $newFileData = implode(
            \PHP_EOL,
            array_merge(
                $preLines,
                [$startMarker],
                $insertion,
                [$endMarker],
                $postLines
            )
        );
        // Write to the start of the file, and truncate it to that length
        fseek($fp, 0);
        $bytes = fwrite($fp, $newFileData);
        if ($bytes) {
            ftruncate($fp, ftell($fp));
        }
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return (bool) $bytes;
    }
}
