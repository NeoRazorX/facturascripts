<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

final class ResponseHeaders
{
    /** @var array */
    private $data = [];

    public function __construct()
    {
        $this->data = [
            'Content-Type' => 'text/html',
            'Strict-Transport-Security' => 'max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
        ];
    }

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $name): string
    {
        return $this->data[$name] ?? '';
    }

    public function remove(string $name): self
    {
        unset($this->data[$name]);

        return $this;
    }

    public function set(string $name, string $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }
}
