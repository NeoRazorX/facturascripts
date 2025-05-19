<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ListFilter;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of NumberFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class NumberFilter extends BaseFilter
{
    /** @var string */
    public $operation;

    /**
     * @param string $key
     * @param string $field
     * @param string $label
     * @param string $operation
     */
    public function __construct(string $key, string $field = '', string $label = '', string $operation = '>=')
    {
        parent::__construct($key, $field, $label);
        $this->operation = $operation;
    }

    public function getDataBaseWhere(array &$where): bool
    {
        if ('' !== $this->value && null !== $this->value) {
            $where[] = new DataBaseWhere($this->field, $this->value, $this->operation);
            return true;
        }

        return false;
    }

    public function render(): string
    {
        return '<div class="col-sm-3 col-lg-2">'
            . '<div class="mb-3">'
            . '<div class="input-group" title="' . static::$i18n->trans($this->label) . '">'
            . ''
            . '<span class="input-group-text">' . $this->operation . ''
            . '</span>'
            . '<input type="text" name="' . $this->name() . '" value="' . $this->value . '" class="form-control" placeholder="'
            . static::$i18n->trans($this->label) . '" autocomplete="off"' . $this->onChange() . $this->readonly() . '/>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    public function setValue($value): void
    {
        parent::setValue(str_replace(',', '.', $value ?? ''));
    }
}
