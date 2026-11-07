<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\UI\Field;

/**
 * Autocompletado con búsqueda en servidor: un SelectField remoto que además
 * admite valores libres (select2 tags). Sustituye al antiguo WidgetAutocomplete
 * sin depender de jQuery UI.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class AutocompleteField extends SelectField
{
    /** true → el usuario puede introducir valores que no estén en la lista. */
    protected bool $allowCustom = true;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->remote = true;
        $this->addEmpty = true;
    }

    /** false → modo estricto: solo valores devueltos por la búsqueda. */
    public function allowCustom(bool $allow = true): static
    {
        $this->allowCustom = $allow;
        return $this;
    }

    public function isAllowCustom(): bool
    {
        return $this->allowCustom;
    }
}
