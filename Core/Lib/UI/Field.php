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

namespace FacturaScripts\Core\Lib\UI;

use FacturaScripts\Core\Lib\UI\Field\AutocompleteField;
use FacturaScripts\Core\Lib\UI\Field\CheckboxField;
use FacturaScripts\Core\Lib\UI\Field\DateField;
use FacturaScripts\Core\Lib\UI\Field\HiddenField;
use FacturaScripts\Core\Lib\UI\Field\NumberField;
use FacturaScripts\Core\Lib\UI\Field\SelectField;
use FacturaScripts\Core\Lib\UI\Field\TextareaField;
use FacturaScripts\Core\Lib\UI\Field\TextField;

/**
 * Fábrica estática de campos, azúcar sintáctico para buildUI():
 * Field::text('nombre'), Field::number('precio'), Field::select('pais')…
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
final class Field
{
    public static function text(string $name): TextField
    {
        return TextField::make($name);
    }

    public static function number(string $name): NumberField
    {
        return NumberField::make($name);
    }

    public static function date(string $name): DateField
    {
        return DateField::make($name);
    }

    public static function textarea(string $name): TextareaField
    {
        return TextareaField::make($name);
    }

    public static function checkbox(string $name): CheckboxField
    {
        return CheckboxField::make($name);
    }

    public static function select(string $name): SelectField
    {
        return SelectField::make($name);
    }

    public static function hidden(string $name): HiddenField
    {
        return HiddenField::make($name);
    }

    public static function autocomplete(string $name): AutocompleteField
    {
        return AutocompleteField::make($name);
    }
}
