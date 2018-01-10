<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base;

/**
 * Description of ListControllerUtils
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListControllerUtils
{

    use Base\Utils;

    /**
     * Database object.
     * 
     * @var Base\DataBase
     */
    private $dataBase;

    public function __construct()
    {
        $this->dataBase = new Base\DataBase();
    }

    /**
     * Creates a list of data from a table
     *
     * @param string $field : Field name with real value
     * @param array $options : Array with configuration values
     *                          [field = Field description, table = table name, where = SQL Where clausule]
     * @param string $search : text to find
     *
     * @return array
     */
    public function autocompleteList($field, $options, $search)
    {
        $result = [];
        if ($this->dataBase->tableExists($options['table'])) {
            $fieldList = $field;
            if ($field !== $options['field']) {
                $fieldList = $fieldList . ', ' . $options['field'];
            }

            $limit = 100;
            $sql = 'SELECT DISTINCT ' . $fieldList
                . ' FROM ' . $options['table']
                . ' WHERE LOWER(' . $options['field'] . ") LIKE '%" . mb_strtolower($search) . "%'"
                . ' ORDER BY ' . $options['field'] . ' ASC';

            $data = $this->dataBase->selectLimit($sql, $limit);
            foreach ($data as $item) {
                $value = $item[$options['field']];
                if ($value !== '') {
                    /**
                     * If the key is  mb_strtolower($item[$field], 'UTF8') then we can't filter by codserie, codalmacen,
                     * etc.
                     */
                    $result[$item[$field]] = self::fixHtml($value);
                }
            }
        }

        return $result;
    }

    public function autocompleteValue($field, $options)
    {
        if (empty($options['value'])) {
            return '';
        }

        $fieldList = $field;
        if ($field !== $options['field']) {
            $fieldList = $fieldList . ', ' . $options['field'];
        }

        $sql = 'SELECT ' . $fieldList . ' FROM ' . $options['table']
            . ' WHERE ' . $field . ' = ' . $this->dataBase->escapeString($options['value']);

        $data = $this->dataBase->select($sql);
        foreach ($data as $item) {
            $value = $item[$options['field']];
            if ($value !== '') {
                /**
                 * If the key is  mb_strtolower($item[$field], 'UTF8') then we can't filter by codserie, codalmacen,
                 * etc.
                 */
                return self::fixHtml($value);
            }
        }
        
        return '';
    }

    /**
     * Returns columns title for megaSearchAction function.
     *
     * @param ListView $view
     * @param int $maxColumns
     *
     * @return array
     */
    public function getTextColumns($view, $maxColumns)
    {
        $result = [];
        foreach ($view->getColumns() as $col) {
            if ($col->display !== 'none' && in_array($col->widget->type, ['text', 'money'], false)) {
                $result[] = $col->widget->fieldName;
                if (count($result) === $maxColumns) {
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Creates a list of data from a table
     *
     * @param string $field : Field name with real value
     * @param array $options : Array with configuration values
     *                          [field = Field description, table = table name, where = SQL Where clausule]
     *
     * @return array
     */
    public function optionlist($field, $options)
    {
        $result = [];
        if ($this->dataBase->tableExists($options['table'])) {
            $fieldList = $field;
            if ($field !== $options['field']) {
                $fieldList = $fieldList . ', ' . $options['field'];
            }

            $sql = 'SELECT DISTINCT ' . $fieldList
                . ' FROM ' . $options['table']
                . ' WHERE COALESCE(' . $options['field'] . ", '')" . " <> ''" . $options['where']
                . ' ORDER BY ' . $options['field'] . ' ASC';

            $data = $this->dataBase->select($sql);
            foreach ($data as $item) {
                $value = $item[$options['field']];
                if ($value !== '') {
                    /**
                     * If the key is  mb_strtolower($item[$field], 'UTF8') then we can't filter by codserie, codalmacen,
                     * etc.
                     */
                    $result[$item[$field]] = self::fixHtml($value);
                }
            }
        }

        return $result;
    }
}
