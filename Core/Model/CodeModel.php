<?php
/**
 * This file is part of FacturaScripts
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
 * Auxiliary model to load a list of codes and their descriptions
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class CodeModel
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
        $this->code = $data['code'];
        $this->description = $data['description'];
    }

    /**
     * Load a CodeModel list (code and description) for the indicated table.
     *
     * @param string  $tableName
     * @param string  $fieldCode
     * @param string  $fieldDescription
     * @param bool $addEmpty
     *
     * @return self[]
     */
    public static function all($tableName, $fieldCode, $fieldDescription, $addEmpty = false)
    {
        $result = [];

        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }

        if (self::$dataBase->tableExists($tableName)) {
            if ($addEmpty) {
                $result[] = new self(['code' => null, 'description' => '']);
            }

            $sql = 'SELECT DISTINCT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description FROM '
                . $tableName . ' ORDER BY 2 ASC';
            $data = self::$dataBase->selectLimit($sql, 1000);
            if (!empty($data)) {
                foreach ($data as $d) {
                    $result[] = new self($d);
                }
            }
        }

        return $result;
    }
}
