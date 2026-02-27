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
class TreeFilter extends SelectFilter
{
    /** @var string */
    public $fieldcode;

    /** @var string */
    public $fieldparent;

    /** @var string */
    public $fieldtitle;

    /** @var string */
    public $table;

    /** @var array */
    public $where;

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
        $this->table = $table;
        $this->fieldparent = $fieldparent;
        $this->fieldcode = empty($fieldcode) ? $field : $fieldcode;
        $this->fieldtitle = empty($fieldtitle) ? $this->fieldcode : $fieldtitle;
        $this->where = $where;

        parent::__construct($key, $field, $label, $this->loadValues());
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

    private function loadValues(): array
    {
        // obtener todos los elementos (cod, titulo, codPadre)
        $dataBase = new DataBase();
        $sql = "SELECT " . $this->fieldcode . ", " . $this->fieldtitle . ", " . $this->fieldparent . " FROM " . $this->table;

        // aplicar where
        if (!empty($this->where)) {
            if (isset($this->where[0]) && $this->where[0] instanceof DataBaseWhere) {
                $sql .= DataBaseWhere::getSQLWhere($this->where);
            } else {
                $sql .= ' WHERE ' . implode(' AND ', $this->where);
            }
        }
        $sql .= " ORDER BY " . $this->fieldtitle;

        // realizar consulta, construir y "maquillar" el árbol
        $rows = $dataBase->select($sql);
        $tree = $this->buildTree($rows);
        return $this->flattenTree($tree);
    }

    /**
     * obtiene los elementos en formato [[cod = x, titulo = x, codPadre = x]...] y los devuelve
     * en un array de [codPadre = [codHijo...], ...] en donde codHijo es [cod = x, titulo = x, codPadre = x]
     * y el elemento padre de todos está dentro de la clave 'ROOT'
     */
    private function buildTree(array $elements): array
    {
        // crear tabla con solo clave id, valor true
        $ids = [];
        foreach ($elements as $element) {
            $ids[$element[$this->fieldcode]] = true;
        }

        // agrupar en una tabla codPadre = [codHijo...] con cada elemento
        $grouped = [];
        foreach ($elements as $element) {
            $pid = $element[$this->fieldparent];
            if (empty($pid) || !isset($ids[$pid])) {
                $pid = 'ROOT';
            }
            $grouped[$pid][] = $element;
        }

        return $this->buildTreeRecursive($grouped, 'ROOT');
    }

    /**
     * Recibe un array de [codPadre = [codHijo...], ...] en donde codHijo es [cod = x, titulo = x, codPadre = x]
     * y el argumento del id del padre o rama a construir.
     * 
     * Hace una llamada recursiva a la función y construye un array en forma de arbol,
     * crea una rama y hace una llamada recursiva a las siguientes ramas.
     * 
     * Devuelve un array en forma de arbol al final, con children = [[cod = x, titulo = x, codPadre = x, children = [elementosHijos...]]] donde
     * elementosHijos es otro [[cod = x, titulo = x, codPadre = x, children = [elementosHijos...]]].
     */
    private function buildTreeRecursive(array &$grouped, $parentId): array
    {
        $branch = [];
        if (isset($grouped[$parentId])) {
            foreach ($grouped[$parentId] as $element) {
                $children = $this->buildTreeRecursive($grouped, $element[$this->fieldcode]);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    /**
     * Recibe un array recursivo de elementos $tree = [elemento1, elemento2, elemento3...] donde
     * cada elemento tiene a su vez un $elemento['children'] = $tree con sus hijos.
     * 
     * Lo formatea para una salida más intuitiva
     */
    private function flattenTree(array $tree, int $level = 0): array
    {
        $result = [];
        foreach ($tree as $node) {
            $prefix = str_repeat('&nbsp;&nbsp;', $level);
            $result[] = [
                'code' => $node[$this->fieldcode],
                'description' => $prefix . htmlspecialchars($node[$this->fieldtitle])
            ];
            if (isset($node['children'])) {
                $result = array_merge($result, $this->flattenTree($node['children'], $level + 1));
            }
        }
        return $result;
    }
}
