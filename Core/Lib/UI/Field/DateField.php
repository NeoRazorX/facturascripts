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

use FacturaScripts\Core\Lib\UI\UIField;
use FacturaScripts\Core\Tools;

/**
 * Campo de fecha (<input type="date">). El valor interno es 'Y-m-d' o null.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class DateField extends UIField
{
    protected function defaultTemplate(): string
    {
        return 'UI/Field/Date.html.twig';
    }

    protected function castFromRequest(mixed $raw): mixed
    {
        if ($raw === null || $raw === '' || !is_string($raw)) {
            return null;
        }
        $time = strtotime($raw);
        return $time === false ? null : date('Y-m-d', $time);
    }

    /** El input type=date exige formato Y-m-d; normaliza valores venidos del modelo. */
    public function valueAttr(): string
    {
        if (empty($this->value)) {
            return '';
        }
        $time = strtotime((string)$this->value);
        return $time === false ? '' : date('Y-m-d', $time);
    }

    public function displayValue(): string
    {
        return empty($this->value) ? '-' : Tools::date($this->value);
    }
}
