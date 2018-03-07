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

namespace FacturaScripts\Core\Base\DataBase;

use FacturaScripts\Core\Base\DataBase;

/**
 * Structure that defines a WHERE condition to filter the model data
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DataBaseWhere
{
    const MATCH_DATE = "/^([\d]{1,2})-([\d]{1,2})-([\d]{4})$/i";
    const MATCH_DATETIME = "/^([\d]{1,2})-([\d]{1,2})-([\d]{4}) ([\d]{1,2}):([\d]{1,2}):([\d]{1,2})$/i";

    /**
     * Link with the active database
     *
     * @var DataBase
     */
    private $dataBase;

    /**
     * Field list to apply the filters to, separated by '|'
     *
     * @var string
     */
    private $fields;

    /**
     * Arithmetic operator that is being used
     *
     * @var string
     */
    private $operator;

    /**
     * Filter value
     *
     * @var string|bool
     */
    private $value;

    /**
     * Logic operator that will be applied to the condition
     *
     * @var string
     */
    private $operation;

    /**
     * DataBaseWhere constructor.
     *
     * @param string      $fields
     * @param string|bool $value
     * @param string      $operator
     * @param string      $operation
     */
    public function __construct($fields, $value, $operator = '=', $operation = 'AND')
    {
        $this->fields = $fields;
        $this->value = $value;
        $this->operator = $operator;
        $this->operation = $operation;
        $this->dataBase = new DataBase();
    }

    /**
     * Formats the date value with the database format
     *
     * @param bool $addTime
     *
     * @return string
     */
    private function format2Date($addTime = false)
    {
        $time = $addTime ? ' H:i:s' : '';

        return "'" . date($this->dataBase->dateStyle() . $time, strtotime($this->value)) . "'";
    }

    /**
     * Returns the value for the operator
     *
     * @return string
     */
    private function getValueFromOperator()
    {
        switch ($this->operator) {
            case 'LIKE':
                if (is_bool($this->value)) {
                    $result = $this->value ? 'TRUE' : 'FALSE';
                } else {
                    $result = "LOWER('%" . $this->dataBase->escapeString($this->value) . "%')";
                }
                break;

            case 'IS':
            case 'IS NOT':
                $result = (string) $this->value;
                break;

            case 'IN':
                $result = '(';
                if (0 === stripos($this->value, 'select ')) {
                    $result .= $this->value;
                } else {
                    $comma = '';
                    foreach (explode(',', $this->value) as $value) {
                        $result .= $comma . "'" . $this->dataBase->escapeString($value) . "'";
                        $comma = ',';
                    }
                }
                $result .= ')';
                break;

            case 'REGEXP':
                $result = "'" . $this->dataBase->escapeString((string) $this->value) . "'";
                break;

            default:
                $result = '';
        }

        return $result;
    }

    /**
     * Returns the value for the type
     *
     * @return string
     */
    private function getValueFromType()
    {
        switch (gettype($this->value)) {
            case 'boolean':
                $result = $this->value ? 'TRUE' : 'FALSE';
                break;

            /// DATE
            case preg_match(self::MATCH_DATE, $this->value) > 0:
                $result = $this->format2Date();
                break;

            /// DATETIME
            case preg_match(self::MATCH_DATETIME, $this->value) > 0:
                $result = $this->format2Date(true);
                break;

            default:
                $result = "'" . $this->dataBase->escapeString($this->value) . "'";
        }

        return $result;
    }

    /**
     * Returns the filter value formatted according to the type
     *
     * @return string
     */
    private function getValue()
    {
        if ($this->value === null) {
            return 'NULL';
        }

        return in_array($this->operator, ['LIKE', 'IS', 'IS NOT', 'IN', 'REGEXP'], false) ? $this->getValueFromOperator() : $this->getValueFromType();
    }

    /**
     * Returns a string to apply to the WHERE clause
     *
     * @param bool $applyOperation
     *
     * @return string
     */
    public function getSQLWhereItem($applyOperation = false)
    {
        $result = '';
        $union = '';
        $value = $this->getValue();
        $fields = explode('|', $this->fields);
        foreach ($fields as $field) {
            if ($this->operator === 'LIKE') {
                $field = 'LOWER(' . $field . ')';
            }
            $result .= $union . $field . ' ' . $this->dataBase->getOperator($this->operator) . ' ' . $value;
            $union = ' OR ';
        }

        if ($result !== '') {
            if (count($fields) > 1) {
                $result = '(' . $result . ')';
            }

            if ($applyOperation) {
                $result = ' ' . $this->operation . ' ' . $result;
            }
        }

        return $result;
    }

    /**
     * Given a DataBaseWhere array, it returns the full WHERE clause
     *
     * @param DataBaseWhere[] $whereItems
     *
     * @return string
     */
    public static function getSQLWhere($whereItems)
    {
        $result = '';
        $join = false;
        foreach ($whereItems as $item) {
            if (isset($item)) {
                $result .= $item->getSQLWhereItem($join);
                $join = true;
            }
        }

        if ($result !== '') {
            $result = ' WHERE ' . $result;
        }

        return $result;
    }

    /**
     * Given a DataBaseWhere array, it returns the field list with  with their values
     * that will be applied as a filter. (It only returns filters with the '=' operator)
     *
     * @param array $whereItems
     *
     * @return array
     */
    public static function getFieldsFilter(array $whereItems)
    {
        $result = [];
        foreach ($whereItems as $item) {
            if ($item->operator === '=') {
                $fields = explode('|', $item->fields);
                foreach ($fields as $field) {
                    $result[$field] = $item->value;
                }
            }
        }

        return $result;
    }
}
