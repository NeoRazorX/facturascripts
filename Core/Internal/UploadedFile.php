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

    public function isUploadedFile(): bool
    {
        return is_uploaded_file($this->tmp_name);
    }

    public function moveTo(string $targetPath): bool
    {
        return move_uploaded_file($this->tmp_name, $targetPath);
    }
}
