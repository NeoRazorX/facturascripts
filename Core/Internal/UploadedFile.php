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
    public $clientFilename;

    /** @var string */
    public $clientMediaType;

    /** @var int */
    public $error;

    /** @var string */
    public $extension;

    /** @var string */
    public $filename;

    /** @var string */
    public $mediaType;

    /** @var int */
    public $size;

    /** @var string */
    public $tmpName;

    public function __construct(array $data)
    {
        $this->clientFilename = $data['name'] ?? '';
        $this->clientMediaType = $data['type'] ?? '';
        $this->error = $data['error'] ?? 0;
        $this->size = $data['size'] ?? 0;
        $this->tmpName = $data['tmp_name'] ?? '';
        $this->filename = $this->tmpName;
        $this->extension = pathinfo($this->clientFilename, PATHINFO_EXTENSION);
        $this->mediaType = $this->clientMediaType;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    public function isUploadedFile(): bool
    {
        return is_uploaded_file($this->tmpName);
    }

    public function moveTo(string $targetPath): bool
    {
        return move_uploaded_file($this->tmpName, $targetPath);
    }
}
