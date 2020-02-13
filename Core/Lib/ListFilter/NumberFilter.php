<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    /**
     *
     * @var string
     */
    public $operation;

    /**
     *
     * @param string $key
     * @param string $field
     * @param string $label
     * @param string $operation
     */
    public function __construct($key, $field = '', $label = '', $operation = '>=')
    {
        parent::__construct($key, $field, $label);
        $this->operation = $operation;
    }

    /**
     *
     * @param array $where
     *
     * @return bool
     */
    public function getDataBaseWhere(array &$where): bool
    {
        if ('' !== $this->value && null !== $this->value) {
            $where[] = new DataBaseWhere($this->field, $this->value, $this->operation);
            return true;
        }

        return false;
    }

    /**
     *
     * @return string
     */
    public function render()
    {
        return '<div class="col-sm-3 col-lg-2">'
            . '<div class="form-group">'
            . '<input type="text" name="' . $this->name() . '" value="' . $this->value . '" class="form-control" placeholder="'
            . $this->operation . ' ' . static::$i18n->trans($this->label) . '" autocomplete="off"' . $this->onChange() . '/>'
            . '</div>'
            . '</div>';
    }
}
