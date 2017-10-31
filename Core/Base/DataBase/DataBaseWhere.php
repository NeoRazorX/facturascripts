<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Estructura para definir una condición WHERE de uso en el
 * filtrado de datos desde los modelos
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DataBaseWhere
{
    const MATCH_DATE = "/^([\d]{1,2})-([\d]{1,2})-([\d]{4})$/i";
    const MATCH_DATETIME = "/^([\d]{1,2})-([\d]{1,2})-([\d]{4}) ([\d]{1,2}):([\d]{1,2}):([\d]{1,2})$/i";

    /**
     * Enlace con la base de datos activa
     *
     * @var DataBase
     */
    private $dataBase;

    /**
     * Lista de campos, separados por '|' a los que se aplica el filtro
     *
     * @var string
     */
    private $fields;

    /**
     * Operador aritmético que se aplica
     *
     * @var string
     */
    private $operator;

    /**
     * Valor por el que se filtra
     *
     * @var string|bool
     */
    private $value;

    /**
     * Operador lógico que se aplicará a la condición
     *
     * @var string
     */
    private $operation;

    /**
     * DataBaseWhere constructor.
     *
     * @param string $fields
     * @param string|bool $value
     * @param string $operator
     * @param string $operation
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
     * Formatea el valor fecha al formato de la base de datos
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
     * Devuelve el valor para el operador
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
                $result = $this->value;
                break;

            default:
                $result = '';
        }

        return $result;
    }

    /**
     * Devuelve el valor por el tipo
     *
     * @return string
     */
    private function getValueFromType()
    {
        switch (gettype($this->value)) {
            case 'boolean':
                $result = $this->value ? 'TRUE' : 'FALSE';
                break;

            case 'integer':
            case 'double':
            case 'float':
                $result = $this->dataBase->escapeString($this->value);
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
     * Devuelve el valor del filtro formateado según el tipo
     *
     * @return string
     */
    private function getValue()
    {
        return (in_array($this->operator, ['LIKE', 'IS'])) ? $this->getValueFromOperator() : $this->getValueFromType();
    }

    /**
     * Devuelve un string para aplicar en la clausula WHERE
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
            if ($this->operator == 'LIKE') {
                $field = 'LOWER(' . $field . ')';
            }
            $result .= $union . $field . ' ' . $this->operator . ' ' . $value;
            $union = ' OR ';
        }

        if ($result != '') {
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
     * Dado un array de DataBaseWhere devuelve la clausula WHERE completa
     *
     * @param array $whereItems
     *
     * @return string
     */
    public static function getSQLWhere(array $whereItems)
    {
        $result = '';
        $join = false;
        foreach ($whereItems as $item) {
            $result .= $item->getSQLWhereItem($join);
            $join = true;
        }

        if ($result != '') {
            $result = ' WHERE ' . $result;
        }

        return $result;
    }
    
    /**
     * Dado un array de DataBaseWhere devuelve la lista de campos y sus valores
     * que se aplicarán como filtro. (Sólo devuelve filtros con operador '='
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
