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

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;

final class SubRequest
{
    /** @var string */
    private $cast = '';

    /** @var array */
    private $data;

    /** @var array */
    private $only = [];

    public function __construct(array $data)
    {
        $this->data = $data;
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

    public function asBool(): self
    {
        $this->cast = 'bool';

        return $this;
    }

    public function asDate(): self
    {
        $this->cast = 'date';

        return $this;
    }

    public function asDateTime(): self
    {
        $this->cast = 'datetime';

        return $this;
    }

    public function asDefault(): self
    {
        $this->cast = '';
        $this->only = [];

        return $this;
    }

    public function asEmail(): self
    {
        $this->cast = 'email';

        return $this;
    }

    public function asFloat(): self
    {
        $this->cast = 'float';

        return $this;
    }

    public function asHour(): self
    {
        $this->cast = 'hour';

        return $this;
    }

    public function asInt(): self
    {
        $this->cast = 'int';

        return $this;
    }

    public function asOnly(array $values): self
    {
        $this->cast = 'only';
        $this->only = $values;

        return $this;
    }

    public function asString(): self
    {
        $this->cast = 'string';

        return $this;
    }

    public function asUrl(): self
    {
        $this->cast = 'url';

        return $this;
    }

    public function get(string $key, $default = null)
    {
        return $this->transform($this->data[$key] ?? $default);
    }

    public function has(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!isset($this->data[$k])) {
                return false;
            }
        }
        return true;
    }

    public function isMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if (isset($this->data[$k])) {
                return false;
            }
        }
        return true;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }


    public function transform($value)
    {
        $cast = $this->cast;
        $only = $this->only;

        // ponemos el cast por defecto
        $this->asDefault();

        if (is_null($value)) {
            return null;
        }

        switch ($cast) {
            case 'bool':
                return (bool)$value;

            case 'date':
                return Tools::date($value);

            case 'datetime':
                return Tools::dateTime($value);

            case 'email':
                return Validator::email($value) ? $value : null;

            case 'float':
                // reemplazamos la coma decimal por un punto
                $value = str_replace(',', '.', $value);
                return (float)$value;

            case 'hour':
                return Tools::hour($value);

            case 'int':
                return (int)$value;

            case 'only':
                return in_array($value, $only) ? $value : null;

            case 'string':
                return (string)$value;

            case 'url':
                return Validator::url($value) ? $value : null;

            default:
                return $value;
        }
    }
}
