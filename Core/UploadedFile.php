<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

final class UploadedFile
{
    /** @var int */
    public $error;

    /** @var string */
    public $name;

    /** @var int */
    public $size;

    /** @var bool */
    public $test = false;

    /** @var string */
    public $tmp_name;

    /** @var string */
    public $type;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (is_array($value)) {
                    $value = $value[0];
                }
                $this->$key = $value;
            }
        }
    }

    public function extension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getClientMimeType(): string
    {
        return mime_content_type($this->tmp_name);
    }

    /**
     * @return string
     * @deprecated replaced by extension() method
     */
    public function getClientOriginalExtension(): string
    {
        return $this->extension();
    }

    public function getClientOriginalName(): string
    {
        return $this->name;
    }

    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error.',
        };
    }

    public static function getMaxFilesize(): int
    {
        $postMax = self::parseFilesize(ini_get('post_max_size'));
        $uploadMax = self::parseFilesize(ini_get('upload_max_filesize'));

        return min($postMax ?: PHP_INT_MAX, $uploadMax ?: PHP_INT_MAX);
    }

    public function getMimeType(): string
    {
        return mime_content_type($this->tmp_name);
    }

    public function getPathname(): string
    {
        return $this->tmp_name;
    }

    public function getRealPath(): string
    {
        return $this->getPathname();
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function isUploaded(): bool
    {
        return $this->test || is_uploaded_file($this->tmp_name);
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && $this->isUploaded();
    }

    public function move(string $destiny, string $destinyName): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if (substr($destiny, -1) !== DIRECTORY_SEPARATOR) {
            $destiny .= DIRECTORY_SEPARATOR;
        }

        return $this->test ?
            rename($this->tmp_name, $destiny . $destinyName) :
            move_uploaded_file($this->tmp_name, $destiny . $destinyName);
    }

    public function moveTo(string $targetPath): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        return $this->test ?
            rename($this->tmp_name, $targetPath) :
            move_uploaded_file($this->tmp_name, $targetPath);
    }

    private static function parseFilesize(string $size): int
    {
        if ('' === $size) {
            return 0;
        }

        $size = strtolower($size);

        $max = ltrim($size, '+');
        if (str_starts_with($max, '0x')) {
            $max = intval($max, 16);
        } elseif (str_starts_with($max, '0')) {
            $max = intval($max, 8);
        } else {
            $max = (int)$max;
        }

        switch (substr($size, -1)) {
            case 't':
                $max *= 1024;
            // no break
            case 'g':
                $max *= 1024;
            // no break
            case 'm':
                $max *= 1024;
            // no break
            case 'k':
                $max *= 1024;
        }

        return $max;
    }
}
