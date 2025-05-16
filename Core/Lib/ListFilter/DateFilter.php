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
 * Description of DateFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DateFilter extends BaseFilter
{
    /** @var bool */
    public $dateTime;

    /** @var string */
    public $operation;

    public function __construct(string $key, string $field = '', string $label = '', string $operation = '>=', bool $dateTime = false)
    {
        parent::__construct($key, $field, $label);

        $this->dateTime = $dateTime;
        $this->operation = $operation;
    }

    public function getDataBaseWhere(array &$where): bool
    {
        if ('' === $this->value || null === $this->value) {
            return false;
        }

        // si es dateTime, añadimos la hora
        switch ($this->operation) {
            case '>':
            case '>=':
            case '<':
                if ($this->dateTime) {
                    $this->value .= ' 00:00:00';
                }
                break;

            case '<=':
                if ($this->dateTime) {
                    $this->value .= ' 23:59:59';
                }
                break;
        }

        $where[] = new DataBaseWhere($this->field, $this->value, $this->operation);
        return true;
    }

    public function render(): string
    {
        $value = empty($this->value) ? '' : date('Y-m-d', strtotime($this->value));
        return '<div class="col-sm-3 col-lg-2">'
            . '<div class="mb-3">'
            . '<div class="input-group" title="' . static::$i18n->trans($this->label) . '">'
            . ''
            . '<span class="input-group-text">' . $this->operation . ''
            . '</span>'
            . '<input type="date" name="' . $this->name() . '" value="' . $value . '" class="form-control"'
            . ' autocomplete="off"' . $this->onChange() . $this->readonly() . '/>'
            . '</div>'
            . '</div>'
            . '</div>';
    }
}
