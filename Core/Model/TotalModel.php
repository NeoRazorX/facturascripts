<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
     * Reset the totals to 0.
     *
     * @param array $totalFields
     */
    private function clearTotals($totalFields)
    {
        foreach ($totalFields as $fieldName) {
            $this->totals[$fieldName] = 0;
        }
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

    /**
     * Load a list of TotalModel (code and fields of statistics) for the indicated table.
     *
     * @param string                   $tableName
     * @param DataBase\DataBaseWhere[] $where
     * @param array                    $fieldList (['key' => 'SUM(total)', 'key2' => 'MAX(total)' ...])
     * @param string                   $fieldCode (for multiples rows agruped by field code)
     *
     * @return static[]
     */
    public static function all($tableName, $where, $fieldList, $fieldCode = '')
    {
        $result = [];

        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }

        if (self::$dataBase->tableExists($tableName)) {
            $sql = 'SELECT ' . self::getFieldSQL($fieldCode, $fieldList);
            $groupby = empty($fieldCode) ? ';' : ' GROUP BY 1 ORDER BY 1;';

            $sqlWhere = DataBase\DataBaseWhere::getSQLWhere($where);
            $sql .= ' FROM ' . $tableName . $sqlWhere . $groupby;
            $data = self::$dataBase->select($sql);
            foreach ($data as $row) {
                $result[] = new static($row);
            }
        }

        /// if it is empty we are obliged to always return a record with the totals to zero
        if (empty($result)) {
            $result[] = new static();
            $result[0]->clearTotals(array_keys($fieldList));
        }

        return $result;
    }
}
