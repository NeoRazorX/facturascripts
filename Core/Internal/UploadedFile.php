<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Internal;

final class UploadedFile
{
    /** @var string */
    public $error;

    /** @var string */
    public $name;

    /** @var int */
    public $size;

    /** @var string */
    public $tmp_name;

    /** @var string */
    public $type;

    public function __construct(array $data = [])
    {
        $this->error = $data['error'] ?? 0;
        $this->name = $data['name'] ?? '';
        $this->size = $data['size'] ?? 0;
        $this->tmp_name = $data['tmp_name'] ?? '';
        $this->type = $data['type'] ?? '';
    }

    public function extension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getClientOriginalName(): string
    {
        return $this->name;
    }

    public function getErrorMessage(): string
    {
        switch ($this->error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';

            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';

            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';

            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';

            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';

            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';

            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';

            default:
                return 'Unknown upload error.';
        }
    }

    public function getMimeType(): string
    {
        return mime_content_type($this->tmp_name);
    }

    public function getPathname(): string
    {
        return $this->tmp_name;
    }

    public function isUploaded(): bool
    {
        return is_uploaded_file($this->tmp_name);
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function moveTo(string $targetPath): bool
    {
        return move_uploaded_file($this->tmp_name, $targetPath);
    }
}
