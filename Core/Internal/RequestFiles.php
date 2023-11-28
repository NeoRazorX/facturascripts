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

final class RequestFiles
{
    /** @return array */
    private $data = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = new UploadedFile($value);
        }
    }

    /**
     * @param string ...$key
     * @return UploadedFile[]
     */
    public function all(string ...$key): array
    {
        if (empty($key)) {
            return $this->data;
        }

        $result = [];
        foreach ($key as $k) {
            $result[$k] = $this->get($k);
        }
        return $result;
    }

    public function get(string $key): ?UploadedFile
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function isMissing(string $key): bool
    {
        return !isset($this->data[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function set(string $key, UploadedFile $value): void
    {
        $this->data[$key] = $value;
    }
}
