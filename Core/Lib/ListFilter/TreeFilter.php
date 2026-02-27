<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */

namespace FacturaScripts\Core\Lib\ListFilter;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Filter all records that match the search term and his child's.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class TreeFilter extends AutocompleteFilter
{
    /** @var string */
    public string $fieldparent;

    /**
     * @param string $key
     * @param string $field
     * @param string $label
     * @param string $table
     * @param string $fieldparent
     * @param string $fieldcode
     * @param string $fieldtitle
     * @param array $where
     */
    public function __construct(string $key, string $field, string $label, string $table, string $fieldparent, string $fieldcode = '', string $fieldtitle = '', array $where = [])
    {
        parent::__construct($key, $field, $label, $table, $fieldcode, $fieldtitle, $where);

        $this->fieldparent = $fieldparent;
    }

    /**
     * @param array $where
     *
     * @return bool
     */
    public function getDataBaseWhere(array &$where): bool
    {
        if (empty($this->fieldparent)) {
            return parent::getDataBaseWhere($where);
        }

        if ('' !== $this->value && null !== $this->value) {
            $ids = $this->getIds();
            $where[] = new DataBaseWhere($this->field, implode(',', $ids), 'IN');
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    private function getIds(): array
    {
        $result = [];

        $sql = "WITH RECURSIVE valuelist AS ("
            . "SELECT " . $this->fieldcode . "," . $this->fieldparent
            . " FROM " . $this->table
            . " WHERE " . $this->fieldcode . " = '" . $this->value . "'"
            . " UNION ALL "
            . "SELECT t1." . $this->fieldcode . ", t1." . $this->fieldparent
            . " FROM " . $this->table . " t1"
            . " INNER JOIN valuelist t2 ON t2." . $this->fieldcode . " = t1." . $this->fieldparent
            . ") "
            . "SELECT * FROM valuelist;";

        $database = new DataBase();
        foreach ($database->select($sql) as $row) {
            $result[] = $row[$this->fieldcode];
        }
        return $result;
    }
}
