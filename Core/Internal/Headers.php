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

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;

class Headers
{
    /** @var array */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function add(array $parameters = []): void
    {
        foreach ($parameters as $key => $value) {
            $this->set($key, $value);
        }
    }

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

    public function get(string $key, $default = null): ?string
    {
        $alt_keys = [
            $key,
            'HTTP_' . strtoupper(str_replace('-', '_', $key)),
            strtoupper(str_replace('-', '_', $key)),
        ];
        foreach ($alt_keys as $alt_key) {
            if (array_key_exists($alt_key, $this->data)) {
                return $this->data[$alt_key];
            }
        }

        return $default;
    }

    public function getArray(string $key, bool $allowNull = true): ?array
    {
        $value = null;
        $alt_keys = [
            $key,
            'HTTP_' . strtoupper(str_replace('-', '_', $key)),
            strtoupper(str_replace('-', '_', $key)),
        ];
        foreach ($alt_keys as $alt_key) {
            if (array_key_exists($alt_key, $this->data)) {
                $value = $this->data[$alt_key];
                break;
            }
        }
        if ($allowNull && empty($value)) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        return (array)$value;
    }

    public function getAlnum(string $key): string
    {
        return preg_replace('/[^[:alnum:]]/', '', $this->get($key) ?? '');
    }

    public function getBool(string $key, bool $allowNull = true): ?bool
    {
        $value = $this->get($key);
        if ($allowNull && is_null($value)) {
            return null;
        }

        return (bool)$value;
    }

    public function getDate(string $key, bool $allowNull = true): ?string
    {
        $value = $this->get($key);
        if (Validator::date($value ?? '')) {
            return Tools::date($value);
        }

        return $allowNull ? null : '';
    }

    public function getDateTime(string $key, bool $allowNull = true): ?string
    {
        $value = $this->get($key);
        if (Validator::datetime($value ?? '') || Validator::date($value ?? '')) {
            return Tools::dateTime($value);
        }

        return $allowNull ? null : '';
    }

    public function getEmail(string $key, bool $allowNull = true): ?string
    {
        $value = $this->get($key);
        if (Validator::email($value ?? '')) {
            return $value;
        }

        return $allowNull ? null : '';
    }

    public function getFloat(string $key, bool $allowNull = true): ?float
    {
        $value = $this->get($key);
        if ($allowNull && is_null($value)) {
            return null;
        }

        // reemplazamos la coma decimal por el punto decimal
        return (float)str_replace(',', '.', $value);
    }

    public function getHour(string $key, bool $allowNull = true): ?string
    {
        $value = $this->get($key);
        if (Validator::hour($value ?? '')) {
            return Tools::hour($value);
        }

        return $allowNull ? null : '';
    }

    public function getInt(string $key, bool $allowNull = true): ?int
    {
        $value = $this->get($key);
        if ($allowNull && is_null($value)) {
            return null;
        }

        return (int)$value;
    }

    public function getOnly(string $key, array $values): ?string
    {
        $value = $this->get($key);
        if (in_array($value, $values)) {
            return $value;
        }

        return null;
    }

    public function getString(string $key, bool $allowNull = true): ?string
    {
        $value = $this->get($key);
        if ($allowNull && is_null($value)) {
            return null;
        }

        return (string)$value;
    }

    public function getUrl(string $key, bool $allowNull = true): ?string
    {
        $value = $this->get($key);
        if (Validator::url($value ?? '')) {
            return $value;
        }

        return $allowNull ? null : '';
    }

    public function has(string ...$key): bool
    {
        foreach ($key as $k) {
            $found = false;
            $alt_keys = [
                $k,
                'HTTP_' . strtoupper(str_replace('-', '_', $k)),
                strtoupper(str_replace('-', '_', $k)),
            ];
            foreach ($alt_keys as $alt_key) {
                if (array_key_exists($alt_key, $this->data)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }

    public function isMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!$this->has($k)) {
                return false;
            }
        }

        return true;
    }

    public function remove(string $key): void
    {
        $alt_keys = [
            $key,
            'HTTP_' . strtoupper(str_replace('-', '_', $key)),
            strtoupper(str_replace('-', '_', $key)),
        ];
        foreach ($alt_keys as $alt_key) {
            if (array_key_exists($alt_key, $this->data)) {
                unset($this->data[$alt_key]);
            }
        }
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
}
