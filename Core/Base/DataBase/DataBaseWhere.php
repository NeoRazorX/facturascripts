<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base\DataBase;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\Utils;

/**
 * Structure that defines a WHERE condition to filter the model data
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class DataBaseWhere
{

    const MATCH_DATE = "/^([\d]{1,2})-([\d]{1,2})-([\d]{4})$/i";
    const MATCH_DATETIME = "/^([\d]{1,2})-([\d]{1,2})-([\d]{4}) ([\d]{1,2}):([\d]{1,2}):([\d]{1,2})$/i";

    /**
     * Link with the active database.
     *
     * @var DataBase
     */
    private $dataBase;

    /**
     * Field list to apply the filters to, separated by '|'.
     *
     * @var string
     */
    private $fields;

    /**
     * Logic operator that will be applied to the condition.
     *
     * @var string
     */
    private $operation;

    /**
     * Arithmetic operator that is being used.
     *
     * @var string
     */
    private $operator;

    /**
     * Filter value.
     *
     * @var mixed
     */
    private $value;

    /**
     * DataBaseWhere constructor.
     *
     * @param string $fields
     * @param mixed  $value
     * @param string $operator
     * @param string $operation
     */
    public function __construct($fields, $value, $operator = '=', $operation = 'AND')
    {
        $this->dataBase = new DataBase();
        $this->fields = $fields;
        $this->operation = $operation;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * Given a DataBaseWhere array, it returns the field list with their values
     * that will be applied as a filter. (It only returns filters with the '=' operator).
     *
     * @param array $whereItems
     *
     * @return array
     */
    public static function getFieldsFilter(array $whereItems)
    {
        $result = [];
        foreach ($whereItems as $item) {
            if ($item->operator !== '=') {
                continue;
            }

            $fields = explode('|', $item->fields);
            foreach ($fields as $field) {
                $result[$field] = $item->value;
            }
        }

        return $result;
    }

    /**
     * Returns a string to apply to the WHERE clause.
     *
     * @param bool   $applyOperation
     * @param string $prefix
     *
     * @return string
     */
    public function getSQLWhereItem($applyOperation = false, $prefix = ''): string
    {
        $fields = explode('|', $this->fields);
        $value = ($this->operator === 'LIKE') ? $this->value : $this->getValue($this->value);
        $result = $this->applyValueToFields($value, $fields);
        if ($result === '') {
            return '';
        }

        if (count($fields) > 1) {
            $result = '(' . $result . ')';
        }

        $result = $prefix . $result;
        if ($applyOperation) {
            $result = ' ' . $this->operation . ' ' . $result;
        }

        return $result;
    }

    /**
     * Given a DataBaseWhere array, it returns the full WHERE clause.
     *
     * @param DataBaseWhere[] $whereItems
     *
     * @return string
     */
    public static function getSQLWhere($whereItems): string
    {
        $result = '';
        $join = false;
        $group = false;

        $keys = array_keys($whereItems);
        foreach ($keys as $num => $key) {
            $next = isset($keys[$num + 1]) ? $keys[$num + 1] : null;

            // Calculate the logical grouping
            $prefix = is_null($next) ? '' : self::getGroupPrefix($whereItems[$next], $group);

            // Calculate the sql clause for the condition
            $result .= $whereItems[$key]->getSQLWhereItem($join, $prefix);
            $join = true;

            // Closes the logical condition of grouping if it exists
            if (!is_null($next) && $group && $whereItems[$next]->operation != 'OR') {
                $result .= ')';
                $group = false;
            }
        }

        if ($result === '') {
            return '';
        }

        // Closes the logical condition of grouping
        if ($group == true) {
            $result .= ')';
        }

        return ' WHERE ' . $result;
    }

    /**
     * Apply one value to a field list.
     *
     * @param string $value
     * @param array  $fields
     *
     * @return string
     */
    private function applyValueToFields($value, $fields): string
    {
        $result = '';
        foreach ($fields as $field) {
            $union = empty($result) ? '' : ' OR ';
            if ($this->operator !== 'LIKE') {
                $result .= $union . $field . ' ' . $this->dataBase->getOperator($this->operator) . ' ' . $value;
                continue;
            }

            /// in LIKE opertator we must break words before search
            $result .= $union . '(';
            $union = '';
            foreach (explode(' ', Utils::noHtml($value)) as $query) {
                $result .= $union . 'LOWER(' . $field . ') ' . $this->dataBase->getOperator($this->operator) . ' ' . $this->getValueFromOperatorLike($query);
                $union = ' AND ';
            }
            $result .= ')';
        }

        return $result;
    }

    /**
     * Formats the date value with the database format.
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
     * Calculate if you need grouping of conditions.
     * It is necessary for logical conditions of type 'OR'
     *
     * @param DataBaseWhere $item
     * @param bool          $group
     *
     * @return string
     */
    private static function getGroupPrefix(&$item, &$group): string
    {
        if ($item->operation == 'OR' && $group == false) {
            $group = true;
            return '(';
        }

        return '';
    }

    /**
     * Return list values for IN operator.
     *
     * @param string $values
     *
     * @return string
     */
    private function getValueFromOperatorIn($values): string
    {
        if (0 === stripos($values, 'select ')) {
            return $values;
        }

        $result = '';
        $comma = '';
        foreach (explode(',', $values) as $value) {
            $result .= $comma . "'" . $this->dataBase->escapeString($value) . "'";
            $comma = ',';
        }
        return $result;
    }

    /**
     * Return value for LIKE operator.
     *
     * @param string $value
     *
     * @return string
     */
    private function getValueFromOperatorLike($value): string
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (strpos($value, '%') === false) {
            return "LOWER('%" . $this->dataBase->escapeString($value) . "%')";
        }

        return "LOWER('" . $this->dataBase->escapeString($value) . "')";
    }

    /**
     * Returns the value for the operator.
     *
     * @param string $value
     *
     * @return string
     */
    private function getValueFromOperator($value): string
    {
        switch ($this->operator) {
            case 'LIKE':
                return $this->getValueFromOperatorLike($value);

            case 'IS':
            case 'IS NOT':
                return (string) $value;

            case 'IN':
                return '(' . $this->getValueFromOperatorIn($value) . ')';

            case 'REGEXP':
                return "'" . $this->dataBase->escapeString((string) $value) . "'";

            default:
                return '';
        }
    }

    /**
     * Returns the value for the type.
     *
     * @param string $value
     *
     * @return string
     */
    private function getValueFromType($value)
    {
        switch (gettype($value)) {
            case 'boolean':
                $result = $value ? 'TRUE' : 'FALSE';
                break;

            /// DATE
            case preg_match(self::MATCH_DATE, $value) > 0:
                $result = $this->format2Date();
                break;

            /// DATETIME
            case preg_match(self::MATCH_DATETIME, $value) > 0:
                $result = $this->format2Date(true);
                break;

            default:
                $result = "'" . $this->dataBase->escapeString($value) . "'";
        }

        return $result;
    }

    /**
     * Returns the filter value formatted according to the type.
     *
     * @param string $value
     *
     * @return string
     */
    private function getValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return in_array($this->operator, ['LIKE', 'IS', 'IS NOT', 'IN', 'REGEXP'], false) ? $this->getValueFromOperator($value) : $this->getValueFromType($value);
    }
}
