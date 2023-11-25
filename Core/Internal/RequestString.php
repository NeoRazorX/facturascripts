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

final class RequestString
{
    /** @var ?string */
    private $value;

    public function __construct(?string $value = '')
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value ?? '';
    }

    public static function create(?string $value = ''): RequestString
    {
        return new self($value);
    }

    public function get(): ?string
    {
        return $this->value;
    }

    public function set(?string $value): RequestString
    {
        $this->value = $value;

        return $this;
    }

    public function toBool(bool $allowNull = true): ?bool
    {
        if ($allowNull && is_null($this->value)) {
            return null;
        }

        return (bool)$this->value;
    }

    public function toDate(bool $allowNull = true): ?string
    {
        if (Validator::date($this->value ?? '')) {
            return Tools::date($this->value);
        }

        return $allowNull ? null : '';
    }

    public function toDateTime(bool $allowNull = true): ?string
    {
        if (Validator::datetime($this->value ?? '') || Validator::date($this->value ?? '')) {
            return Tools::dateTime($this->value);
        }

        return $allowNull ? null : '';
    }

    public function toEmail(bool $allowNull = true): ?string
    {
        if (Validator::email($this->value ?? '')) {
            return $this->value;
        }

        return $allowNull ? null : '';
    }

    public function toFloat(bool $allowNull = true): ?float
    {
        if ($allowNull && is_null($this->value)) {
            return null;
        }

        // reemplazamos la coma decimal por el punto decimal
        return (float)str_replace(',', '.', $this->value);
    }

    public function toHour(bool $allowNull = true): ?string
    {
        if (Validator::hour($this->value ?? '')) {
            return Tools::hour($this->value);
        }

        return $allowNull ? null : '';
    }

    public function toInt(bool $allowNull = true): ?int
    {
        if ($allowNull && is_null($this->value)) {
            return null;
        }

        return (int)$this->value;
    }

    public function toOnly(array $values): ?string
    {
        if (in_array($this->value, $values)) {
            return $this->value;
        }

        return null;
    }

    public function toString(bool $allowNull = true): ?string
    {
        if ($allowNull && is_null($this->value)) {
            return null;
        }

        return (string)$this->value;
    }

    public function toUrl(bool $allowNull = true): ?string
    {
        if (Validator::url($this->value ?? '')) {
            return $this->value;
        }

        return $allowNull ? null : '';
    }
}
