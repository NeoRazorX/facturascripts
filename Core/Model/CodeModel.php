<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ToolBox;

/**
 * Auxiliary model to load a list of codes and their descriptions
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class CodeModel
{

    const ALL_LIMIT = 500;
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';
    const SEARCH_LIMIT = 50;

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

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
            $this->description = $data['description'];
        }
    }

    /**
     * Load a CodeModel list (code and description) for the indicated table.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $fieldDescription
     * @param bool   $addEmpty
     * @param array  $where
     *
     * @return static[]
     */
    public static function all($tableName, $fieldCode, $fieldDescription, $addEmpty = true, $where = [])
    {
        $result = [];
        if ($addEmpty) {
            $result[] = new static(['code' => null, 'description' => '------']);
        }

        /// is a table or a model?
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            return array_merge($result, $model->codeModelAll($fieldCode));
        }

        self::initDataBase();
        if (!self::$dataBase->tableExists($tableName)) {
            static::toolBox()->i18nLog()->error('table-not-found', ['%tableName%' => $tableName]);
            return $result;
        }

        $sql = 'SELECT DISTINCT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description '
            . 'FROM ' . $tableName . DataBaseWhere::getSQLWhere($where) . ' ORDER BY 2 ASC';
        foreach (self::$dataBase->selectLimit($sql, self::ALL_LIMIT) as $row) {
            $result[] = new static($row);
        }

        return $result;
    }

    /**
     * Convert an associative array (code and value) into a CodeModel array.
     * 
     * @param array $data
     *
     * @return static[]
     */
    public static function array2codeModel(array $data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            $row = ['code' => $key, 'description' => $value];
            $result[] = new static($row);
        }

        return $result;
    }

    /**
     * Load a CodeModel list (code and description) for the indicated table and search.
     *
     * @param string          $tableName
     * @param string          $fieldCode
     * @param string          $fieldDescription
     * @param string          $query
     * @param DataBaseWhere[] $where
     *
     * @return static[]
     */
    public static function search($tableName, $fieldCode, $fieldDescription, $query, $where = [])
    {
        /// is a table or a model?
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if (\class_exists($modelClass)) {
            $model = new $modelClass();
            return $model->codeModelSearch($query, $fieldCode, $where);
        }

        $fields = $fieldCode . '|' . $fieldDescription;
        $where[] = new DataBaseWhere($fields, \mb_strtolower($query, 'UTF8'), 'LIKE');
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
     * @return static
     */
    public function get($tableName, $fieldCode, $code, $fieldDescription)
    {
        /// is a table or a model?
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            $where = [new DataBaseWhere($fieldCode, $code)];
            if ($model->loadFromCode('', $where)) {
                return new static(['code' => $model->{$fieldCode}, 'description' => $model->primaryDescription()]);
            }

            return new static();
        }

        self::initDataBase();
        if (self::$dataBase->tableExists($tableName)) {
            $sql = 'SELECT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description FROM '
                . $tableName . ' WHERE ' . $fieldCode . ' = ' . self::$dataBase->var2str($code);
            $data = self::$dataBase->selectLimit($sql, 1);
            return empty($data) ? new static() : new static($data[0]);
        }

        return new static();
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
        return empty($model->description) ? $code : $model->description;
    }

    /**
     * Inits database connection.
     */
    protected static function initDataBase()
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }
    }

    /**
     *
     * @return ToolBox
     */
    protected static function toolBox()
    {
        return new ToolBox();
    }
}
