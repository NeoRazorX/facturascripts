<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of AutocompleteFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AutocompleteFilter extends BaseFilter
{

    /**
     *
     * @var string
     */
    public $fieldcode;

    /**
     *
     * @var string
     */
    public $fieldtitle;

    /**
     *
     * @var string
     */
    public $table;

    /**
     *
     * @var array
     */
    public $where;

    public function __construct($key, $field, $label, $table, $fieldcode = '', $fieldtitle = '', $where = [])
    {
        parent::__construct($key, $field, $label);
        $this->table = $table;
        $this->fieldcode = empty($fieldcode) ? $this->field : $fieldcode;
        $this->fieldtitle = empty($fieldtitle) ? $this->fieldcode : $fieldtitle;
        $this->where = $where;
    }

    public function getDataBaseWhere(array &$where)
    {
        return $where;
    }
    
    public function render()
    {
        return '';
    }
}
