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

namespace FacturaScripts\Core\Template\UI;

use Exception;
use FacturaScripts\Core\Validator;

abstract class Component
{
    /** @var string */
    private $name;

    /** @var string */
    private $parent_id = '';

    /** @var int */
    private $position = 0;

    abstract public function render(string $context = ''): string;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function id(): string
    {
        return empty($this->parent_id) ?
            $this->name :
            $this->parent_id . '_' . $this->name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function parentId(): string
    {
        return $this->parent_id;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function setName(string $name): self
    {
        if (false === Validator::alphaNumeric($name, '_')) {
            throw new Exception('Invalid component name: ' . $name);
        }

        $this->name = $name;

        return $this;
    }

    public function setParent(Component $parent): self
    {
        $this->parent_id = $parent->id();

        return $this;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}