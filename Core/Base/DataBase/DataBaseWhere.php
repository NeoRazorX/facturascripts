<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Structure that defines a WHERE condition to filter the model data
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class DataBaseWhere
{

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

        /// check restrictions with null values
        if (null === $value && $operator === '=') {
            $this->operator = 'IS';
        } elseif (null === $value && $operator === '!=') {
            $this->operator = 'IS NOT';
        }
    }

    /**
     * Given a list of fields with operators:
     * '|' for OR operations
     * ',' for AND operations
     * Returns an array with the field (key) and the operation (value).
     *
     * @param string $fields
     *
     * @return array
     */
    public static function applyOperation(string $fields)
    {
        if (empty($fields)) {
            return [];
        }

        $result = [];
        foreach (\explode(',', $fields) as $field) {
            if ($field !== '' && \strpos($field, '|') === false) {
                $result[$field] = 'AND';
            }
        }
        foreach (\explode('|', $fields) as $field) {
            if ($field !== '' && \strpos($field, ',') === false) {
                $result[$field] = 'OR';
            }
        }

        return $result;
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

            $fields = \explode('|', $item->fields);
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
        $fields = \explode('|', $this->fields);
        $result = $this->applyValueToFields($this->value, $fields);
        if ($result === '') {
            return '';
        }

        if (\count($fields) > 1) {
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

        $keys = \array_keys($whereItems);
        foreach ($keys as $num => $key) {
            $next = isset($keys[$num + 1]) ? $keys[$num + 1] : null;

            // Calculate the logical grouping
            $prefix = \is_null($next) ? '' : self::getGroupPrefix($whereItems[$next], $group);

            // Calculate the sql clause for the condition
            $result .= $whereItems[$key]->getSQLWhereItem($join, $prefix);
            $join = true;

            // Closes the logical condition of grouping if it exists
            if (null !== $next && $group && $whereItems[$next]->operation != 'OR') {
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
     * @param mixed $value
     * @param array $fields
     *
     * @return string
     */
    private function applyValueToFields($value, $fields): string
    {
        $result = '';
        foreach ($fields as $field) {
            $union = empty($result) ? '' : ' OR ';
            switch ($this->operator) {
                case 'LIKE':
                    $result .= $union . 'LOWER(' . $this->escapeColumn($field) . ') '
                        . $this->dataBase->getOperator($this->operator) . ' ' . $this->getValueFromOperatorLike($value);
                    break;

                case 'XLIKE':
                    $result .= $union . '(';
                    $union2 = '';
                    foreach (\explode(' ', $value) as $query) {
                        $result .= $union2 . 'LOWER(' . $this->escapeColumn($field) . ') '
                            . $this->dataBase->getOperator('LIKE') . ' ' . $this->getValueFromOperatorLike($query);
                        $union2 = ' AND ';
                    }
                    $result .= ')';
                    break;

                default:
                    $result .= $union . $this->escapeColumn($field) . ' '
                        . $this->dataBase->getOperator($this->operator) . ' ' . $this->getValue($value);
                    break;
            }
        }

        return $result;
    }

    /**
     * 
     * @param string $column
     *
     * @return string
     */
    private function escapeColumn($column)
    {
        $exclude = ['.', 'CAST('];
        foreach ($exclude as $char) {
            if (\strpos($column, $char) !== false) {
                return $column;
            }
        }

        return $this->dataBase->escapeColumn($column);
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
        if (0 === \stripos($values, 'select ')) {
            return $values;
        }

        $result = '';
        $comma = '';
        foreach (\explode(',', $values) as $value) {
            $result .= $comma . $this->dataBase->var2str($value);
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
        if (\is_null($value) || \is_bool($value)) {
            return $this->dataBase->var2str($value);
        }

        if (\strpos($value, '%') === false) {
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
            case 'IN':
            case 'NOT IN':
                return '(' . $this->getValueFromOperatorIn($value) . ')';

            case 'LIKE':
            case 'XLIKE':
                return $this->getValueFromOperatorLike($value);

            default:
                return $this->dataBase->var2str($value);
        }
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
        if (\in_array($this->operator, ['IN', 'LIKE', 'NOT IN', 'XLIKE'], false)) {
            return $this->getValueFromOperator($value);
        }

        if (0 === \strpos($value, 'field:')) {
            return $this->dataBase->escapeColumn(\substr($value, 6));
        }

        return $this->dataBase->var2str($value);
    }
}
