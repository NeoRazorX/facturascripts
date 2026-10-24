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
 * Casilla de verificación. Valor interno bool.
 *
 * La plantilla emite un <input type="hidden" value="0"> antes del checkbox para
 * que los desmarcados también viajen en el POST (funciona con y sin JS).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class CheckboxField extends UIField
{
    protected function defaultTemplate(): string
    {
        return 'UI/Field/Checkbox.html.twig';
    }

    public function colClass(): string
    {
        return $this->cols <= 0 ? 'col-12 col-sm-auto' : parent::colClass();
    }

    protected function castFromRequest(mixed $raw): mixed
    {
        // el hidden envía '0'; el checkbox marcado lo sobrescribe con '1'
        return in_array($raw, ['1', 'TRUE', 'true', 1, true], true);
    }

    public function isChecked(): bool
    {
        return in_array($this->value, ['1', 'TRUE', 'true', 1, true], true);
    }

    public function displayValue(): string
    {
        return $this->isChecked() ? Tools::lang()->trans('yes') : Tools::lang()->trans('no');
    }
}
