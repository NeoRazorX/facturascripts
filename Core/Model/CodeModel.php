<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Auxiliary model to load a list of codes and their descriptions
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class CodeModel
{

    const ALL_LIMIT = 1000;
    const SEARCH_LIMIT = 50;

    /**
     * It provides direct access to the database.
     *
     * @var Base\DataBase
     */
    private static $dataBase;

    /**
     * Value of the code field of the model read.
     *
     * @var string
     */
    public $code;

    /**
     * Value of the field description of the model read.
     *
     * @var string
     */
    public $description;

    /**
     * Constructor and class initializer.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        if (empty($data)) {
            $this->code = '';
            $this->description = '';
        } else {
            $this->code = $data['code'];
            $this->description = Base\Utils::fixHtml($data['description']);
        }
    }

    /**
     * Load a CodeModel list (code and description) for the indicated table.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $fieldDescription
     * @param bool   $addEmpty
     * @param DataBaseWhere[] $where
     *
     * @return self[]
     */
    public static function all($tableName, $fieldCode, $fieldDescription, $addEmpty = true, $where = [])
    {
        $result = [];

        if (self::$dataBase === null) {
            self::$dataBase = new Base\DataBase();
        }

        if (self::$dataBase->tableExists($tableName)) {
            if ($addEmpty) {
                $result[] = new self(['code' => null, 'description' => '------']);
            }
            $sqlWhere = DataBaseWhere::getSQLWhere($where);
            $sql = 'SELECT DISTINCT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description '
                . 'FROM ' . $tableName . $sqlWhere . ' ORDER BY 2 ASC';
            $data = self::$dataBase->selectLimit($sql, self::ALL_LIMIT);
            if (!empty($data)) {
                foreach ($data as $d) {
                    $result[] = new self($d);
                }
            }
        }

        return $result;
    }

    /**
     * Load a CodeModel list (code and description) for the indicated table and search.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $fieldDescription
     * @param string $search
     *
     * @return self[]
     */
    public static function search($tableName, $fieldCode, $fieldDescription, $search)
    {
        $fields = $fieldCode . '|' . $fieldDescription;
        $where = [new DataBaseWhere($fields, mb_strtolower($search), 'LIKE')];
        return self::all($tableName, $fieldCode, $fieldDescription, false, $where);
    }

    /**
     * Returns a codemodel with the selected data.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $code
     * @param string $fieldDescription
     *
     * @return self
     */
    public function get($tableName, $fieldCode, $code, $fieldDescription)
    {
        if (self::$dataBase === null) {
            self::$dataBase = new Base\DataBase();
        }

        if (self::$dataBase->tableExists($tableName)) {
            $sql = 'SELECT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description FROM '
                . $tableName . ' WHERE ' . $fieldCode . ' = ' . self::$dataBase->var2str($code);
            $data = self::$dataBase->selectLimit($sql, 1);
            if (!empty($data)) {
                return new self($data[0]);
            }
        }

        return new self();
    }

    /**
     * Returns a description with the selected data.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $code
     * @param string $fieldDescription
     *
     * @return string
     */
    public function getDescription($tableName, $fieldCode, $code, $fieldDescription)
    {
        $model = $this->get($tableName, $fieldCode, $code, $fieldDescription);

        return $model->description;
    }
}
