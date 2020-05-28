<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Auxiliary model to load a list of totals
 * with or without grouping by code.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class TotalModel
{

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    private static $dataBase;

    /**
     * Value of the code field of the model read.
     *
     * @var string
     */
    public $code;

    /**
     * Total values of the fields of the read model.
     *
     * @var array
     */
    public $totals;

    /**
     * Constructor and class initializer
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->code = '';
        $this->totals = [];
        foreach ($data as $field => $value) {
            if ($field === 'code') {
                $this->code = $value;
                continue;
            }

            $this->totals[$field] = empty($value) ? 0 : $value;
        }
    }

    /**
     * Load a list of TotalModel (code and fields of statistics) for the indicated table.
     *
     * @param string          $tableName
     * @param DataBaseWhere[] $where
     * @param array           $fieldList (['key' => 'SUM(total)', 'key2' => 'MAX(total)' ...])
     * @param string          $fieldCode (for multiples rows agruped by field code)
     *
     * @return static[]
     */
    public static function all($tableName, $where, $fieldList, $fieldCode = '')
    {
        $result = [];
        if (static::dataBase()->tableExists($tableName)) {
            $sql = 'SELECT ' . static::getFieldSQL($fieldCode, $fieldList);
            $groupby = empty($fieldCode) ? ';' : ' GROUP BY 1 ORDER BY 1;';

            $sqlWhere = DataBaseWhere::getSQLWhere($where);
            $sql .= ' FROM ' . $tableName . $sqlWhere . $groupby;
            $data = static::dataBase()->select($sql);
            foreach ($data as $row) {
                $result[] = new static($row);
            }
        }

        /// if it is empty we are obliged to always return a record with the totals to zero
        if (empty($result)) {
            $item = new static();
            $item->clearTotals(\array_keys($fieldList));
            return [$item];
        }

        return $result;
    }

    /**
     * Reset the totals to 0.0
     *
     * @param array $totalFields
     */
    public function clearTotals($totalFields)
    {
        foreach ($totalFields as $fieldName) {
            $this->totals[$fieldName] = 0.0;
        }
    }

    /**
     * 
     * @param string          $tableName
     * @param string          $fieldName
     * @param DataBaseWhere[] $where
     *
     * @return float
     */
    public static function sum($tableName, $fieldName, $where): float
    {
        if (static::dataBase()->tableExists($tableName)) {
            $sql = 'SELECT SUM(' . static::dataBase()->escapeColumn($fieldName) . ') as sum'
                . ' FROM ' . static::dataBase()->escapeColumn($tableName)
                . DataBaseWhere::getSQLWhere($where);
            foreach (static::dataBase()->select($sql) as $row) {
                return (float) $row['sum'];
            }
        }

        return 0.0;
    }

    /**
     * 
     * @return DataBase
     */
    private static function dataBase()
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }

        return self::$dataBase;
    }

    /**
     * Returns the / fields as part of the SQL query.
     *
     * @param string $fieldCode
     * @param array  $fieldList
     *
     * @return string
     */
    private static function getFieldSQL($fieldCode, $fieldList)
    {
        $result = '';
        $comma = '';

        if (!empty($fieldCode)) {
            $result .= $fieldCode . ' AS code';
            $comma = ', ';
        }

        foreach ($fieldList as $fieldName => $fieldSQL) {
            $result .= $comma . $fieldSQL . ' AS ' . $fieldName;
            $comma = ', ';
        }

        return $result;
    }
}
