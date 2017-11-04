<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase;

/**
 * Modelo Auxiliar para cargar una lista de totales
 * con o sin agrupación por código.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class TotalModel
{

    /**
     * Proporciona acceso directo a la base de datos.
     *
     * @var DataBase
     */
    private static $dataBase;

    /**
     * Valor del campo código del modelo leido
     *
     * @var string
     */
    public $code;

    /**
     * Valores de totales de los campos del modelo leido
     *
     * @var array
     */
    public $totals;

    /**
     * Constructor e inicializador de la clase
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

    private function clearTotals($totalFields)
    {
        foreach ($totalFields as $fieldName) {
            $this->totals[$fieldName] = 0;
        }
    }

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
     * Carga una lista de TotalModel (código y campos de estadisticos) para la tabla indicada
     *
     * @param string  $tableName
     * @param DataBaseWhere $where
     * @param array  $fieldList      (['key' => 'SUM(total)', 'key2' => 'MAX(total)' ...])
     * @param string $fieldCode      (for multiples rows agruped by field code)
     *
     * @return self[]
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
            foreach ($data as $d) {
                $result[] = new self($d);
            }
        }

        /// if it is empty we are obliged to always return a record with the totals to zero
        if (empty($result)) {
            $result[] = new self();
            $result[0]->clearTotals(array_keys($fieldList));
        }

        return $result;
    }
}
