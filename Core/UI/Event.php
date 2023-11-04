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

namespace FacturaScripts\Core\UI;

class Event
{
    /** @var string */
    public $component_id;

    /** @var string */
    public $function;

    /** @var int */
    public $position;

    /** @var string */
    public $type;

    public function __construct(string $component_id, string $type, string $function, int $position = 0)
    {
        $this->component_id = $component_id;
        $this->function = $function;
        $this->position = $position;
        $this->type = $type;
    }

    public function functionName(): string
    {
        return $this->isFunctionOfComponent() ? substr($this->function, 10) : $this->function;
    }

    public function isFunctionOfComponent(): bool
    {
        // si la función comienza por component: es una función de componente
        return strpos($this->function, 'component:') === 0;
    }

    public function name(): string
    {
        return $this->component_id . ':' . $this->type;
    }

    public function position(): int
    {
        return $this->position;
    }
}
