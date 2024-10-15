<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\UploadedFile;

final class RequestFiles
{
    /** @return array */
    private $data = [];

    public function __construct(array $data = [])
    {
        $files = [];
        foreach ($data as $key => $value) {
            if (false === isset($value['size'])) {
                continue;
            } elseif (is_array($value['size'])) {
                $files[$key] = $this->convertArrayFiles($value);
            } elseif ($value['size'] > 0) {
                $files[$key] = $value;
            }
        }

        foreach ($files as $key => $value) {
            if (isset($value[0]) && is_array($value[0])) {
                foreach ($value as $file) {
                    $this->data[$key][] = new UploadedFile($file);
                }
            } else {
                $this->data[$key] = new UploadedFile($value);
            }
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
            if (false === $this->has($k)) {
                continue;
            }
            $result[$k] = $this->data[$k];
        }
        return $result;
    }

    public function get(string $key): ?UploadedFile
    {
        if ($this->has($key) && $this->data[$key] instanceof UploadedFile) {
            return $this->data[$key];
        }

        return null;
    }

    public function getArray(string $key): array
    {
        if ($this->has($key) && is_array($this->data[$key])) {
            return $this->data[$key];
        }

        return [];
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

    private function convertArrayFiles($file_post): array
    {
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                if ($file_post['size'][$i] > 0) {
                    $file_ary[$i][$key] = $file_post[$key][$i];
                }
            }
        }

        return $file_ary;
    }
}
